<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

#[Signature('app:encrypt {--source= : Comma-separated list of source directories to encrypt (default: from config)}
    {--destination= : Destination directory for the encrypted project mirror (default: from config)}
    {--keylength= : Length of the encryption key (default: from config)}
    {--exclude= : Comma-separated list of additional paths to exclude (merged with config)}
    {--full : Force a full rebuild instead of incremental sync}
    {--force : Skip confirmation when clearing destination during full rebuild}
    {--no-commit : Skip auto-commit inside the destination repo}')]
#[Description('Builds an encrypted mirror of the project: source paths are bolt-encrypted, everything else is copied as-is. Incremental by default.')]
class EncryptApplication extends Command
{
    private const string BOLT_MARKER = '##!!!##';

    private const int DIR_PERMISSIONS = 0755;

    private const string SYNC_STATE_FILE = '.sync-state';

    public function handle(): int
    {
        if (! $this->ensureBoltLoaded()) {
            return self::FAILURE;
        }

        [$sources, $destination, $keyLength, $excludes] = $this->resolveOptions();

        $destinationPath = base_path($destination);
        $currentHash = $this->gitHead(base_path());
        $syncStatePath = $destinationPath.'/'.self::SYNC_STATE_FILE;
        $lastHash = File::exists($syncStatePath) ? trim(File::get($syncStatePath)) : null;

        $needsFullRebuild = $this->option('full')
            || ! File::exists($destinationPath)
            || ! $lastHash
            || ! $currentHash
            || ! $this->isValidCommit($lastHash);

        if ($needsFullRebuild || $lastHash === $currentHash) {
            if (! $this->prepareDestination($destinationPath)) {
                return self::FAILURE;
            }

            $this->fullRebuild($sources, $destination, $keyLength, $excludes);
        } else {
            $this->incrementalSync($lastHash, $sources, $destination, $keyLength, $excludes);
        }

        if ($currentHash) {
            File::put($syncStatePath, $currentHash);
        }

        if (! $this->option('no-commit')) {
            $this->commitInDestination($destinationPath, $currentHash);
        }

        $this->info('Encryption completed successfully!');
        $this->info("Destination directory: {$destination}");

        return self::SUCCESS;
    }

    private function ensureBoltLoaded(): bool
    {
        if (extension_loaded('bolt')) {
            return true;
        }

        $this->error('Please install bolt.so https://phpBolt.com');
        $this->error('PHP Version: '.phpversion());
        $this->error('INI file location: '.php_ini_scanned_files());
        $this->error('Extension dir: '.ini_get('extension_dir'));

        return false;
    }

    /**
     * @return array{0: array<string>, 1: string, 2: int, 3: array<string>}
     */
    private function resolveOptions(): array
    {
        $sources = $this->option('source')
            ? array_map('trim', explode(',', $this->option('source')))
            : config('encrypter.source', ['app', 'database', 'routes']);

        $destination = $this->option('destination')
            ?: config('encrypter.destination', 'dist');

        $keyLength = (int) ($this->option('keylength')
            ?: config('encrypter.key_length', 6));

        $excludes = config('encrypter.exclude', []);

        if ($this->option('exclude')) {
            $excludes = array_merge($excludes, array_map('trim', explode(',', $this->option('exclude'))));
        }

        // Always exclude the destination directory itself.
        $excludes[] = $destination;

        return [$sources, $destination, $keyLength, array_values(array_unique($excludes))];
    }

    private function prepareDestination(string $destinationPath): bool
    {
        if (! File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, self::DIR_PERMISSIONS, true);

            return true;
        }

        if (! $this->option('force') && ! $this->confirm('Destination exists. Clear its contents (preserving .git)?')) {
            $this->line('Command canceled.');

            return false;
        }

        foreach (new FilesystemIterator($destinationPath) as $item) {
            if ($item->getFilename() === '.git') {
                continue;
            }

            if ($item->isDir()) {
                File::deleteDirectory($item->getPathname());
            } else {
                File::delete($item->getPathname());
            }
        }

        return true;
    }

    /**
     * @param  array<string>  $sources
     * @param  array<string>  $excludes
     */
    private function fullRebuild(array $sources, string $destination, int $keyLength, array $excludes): void
    {
        $files = $this->collectProjectFiles($excludes);

        $this->info('Building '.count($files).' file(s)...');

        $this->withProgressBar($files, function (string $relativePath) use ($destination, $keyLength, $sources) {
            $this->processFile($relativePath, $destination, $keyLength, $sources);
        });

        $this->newLine();
    }

    /**
     * @param  array<string>  $sources
     * @param  array<string>  $excludes
     */
    private function incrementalSync(string $lastHash, array $sources, string $destination, int $keyLength, array $excludes): void
    {
        $this->deleteRemovedFiles($lastHash, $destination, $excludes);
        $this->syncChangedFiles($lastHash, $sources, $destination, $keyLength, $excludes);
    }

    /**
     * @param  array<string>  $excludes
     */
    private function deleteRemovedFiles(string $lastHash, string $destination, array $excludes): void
    {
        $result = Process::path(base_path())->run([
            'git', 'diff', '--name-only', '--diff-filter=D', "{$lastHash}..HEAD",
        ]);

        $deleted = array_filter(explode("\n", trim($result->output())));

        foreach ($deleted as $relativePath) {
            if ($this->isExcluded($relativePath, $excludes)) {
                continue;
            }

            $targetPath = base_path("{$destination}/{$relativePath}");

            if (File::exists($targetPath)) {
                File::delete($targetPath);
                $this->line("  Deleted: {$relativePath}");
            }
        }
    }

    /**
     * @param  array<string>  $sources
     * @param  array<string>  $excludes
     */
    private function syncChangedFiles(string $lastHash, array $sources, string $destination, int $keyLength, array $excludes): void
    {
        $result = Process::path(base_path())->run([
            'git', 'diff', '--name-only', '--diff-filter=ACMRT', "{$lastHash}..HEAD",
        ]);

        $files = array_values(array_filter(
            explode("\n", trim($result->output())),
            fn (string $file): bool => $file !== ''
                && ! $this->isExcluded($file, $excludes)
                && File::exists(base_path($file))
        ));

        if (empty($files)) {
            $this->info('No relevant files changed.');

            return;
        }

        $this->info('Syncing '.count($files).' changed file(s)...');

        $this->withProgressBar($files, function (string $relativePath) use ($destination, $keyLength, $sources) {
            $this->processFile($relativePath, $destination, $keyLength, $sources);
        });

        $this->newLine();
    }

    /**
     * @param  array<string>  $excludes
     * @return array<string>
     */
    private function collectProjectFiles(array $excludes): array
    {
        $result = Process::path(base_path())->run(['git', 'ls-files']);

        if (! $result->successful()) {
            $this->error('git ls-files failed: '.$result->errorOutput());

            return [];
        }

        return array_values(array_filter(
            explode("\n", trim($result->output())),
            fn (string $file): bool => $file !== ''
                && ! $this->isExcluded($file, $excludes)
                && is_file(base_path($file))
        ));
    }

    /**
     * @param  array<string>  $sources
     */
    private function processFile(string $relativePath, string $destination, int $keyLength, array $sources): void
    {
        $sourcePath = base_path($relativePath);

        // Skip anything that isn't a regular file: directories, dir-symlinks, sockets, etc.
        if (! is_file($sourcePath) || is_dir($sourcePath)) {
            return;
        }

        $targetPath = base_path("{$destination}/{$relativePath}");

        File::ensureDirectoryExists(dirname($targetPath), self::DIR_PERMISSIONS);

        if ($this->shouldEncrypt($relativePath, $sources)) {
            $this->encryptFile($sourcePath, $targetPath, $relativePath, $keyLength);

            return;
        }

        File::copy($sourcePath, $targetPath);
    }

    private function encryptFile(string $sourcePath, string $targetPath, string $relativePath, int $keyLength): void
    {
        $contents = File::get($sourcePath);
        $contents = preg_replace('/<\?php/', '', $contents, 1);

        $key = Str::random($keyLength);
        $cipher = bolt_encrypt($contents, $key);

        if ($cipher === false) {
            $this->error("Failed to encrypt: {$relativePath}");

            return;
        }

        $prepend = "<?php\nbolt_decrypt(__FILE__, '{$key}'); return 0;\n".self::BOLT_MARKER;

        File::put($targetPath, $prepend.$cipher);
    }

    /**
     * @param  array<string>  $sources
     */
    private function shouldEncrypt(string $relativePath, array $sources): bool
    {
        if (Str::endsWith($relativePath, '.blade.php')) {
            return false;
        }

        if (pathinfo($relativePath, PATHINFO_EXTENSION) !== 'php') {
            return false;
        }

        return $this->isWithinPaths($relativePath, $sources);
    }

    /**
     * @param  array<string>  $excludes
     */
    private function isExcluded(string $relativePath, array $excludes): bool
    {
        return $this->isWithinPaths($relativePath, $excludes);
    }

    /**
     * @param  array<string>  $paths
     */
    private function isWithinPaths(string $relativePath, array $paths): bool
    {
        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }

            if ($relativePath === $path || Str::startsWith($relativePath, $path.'/')) {
                return true;
            }
        }

        return false;
    }

    private function gitHead(string $repoPath): ?string
    {
        $result = Process::path($repoPath)->run(['git', 'rev-parse', 'HEAD']);

        return $result->successful() ? trim($result->output()) : null;
    }

    private function isValidCommit(string $hash): bool
    {
        $result = Process::path(base_path())->run(['git', 'cat-file', '-t', $hash]);

        return $result->successful() && trim($result->output()) === 'commit';
    }

    private function commitInDestination(string $destinationPath, ?string $sourceHash): void
    {
        if (! File::exists($destinationPath.'/.git')) {
            return;
        }

        Process::path($destinationPath)->run(['git', 'add', '-A']);

        $status = Process::path($destinationPath)->run(['git', 'status', '--porcelain']);

        if (empty(trim($status->output()))) {
            return;
        }

        $message = $sourceHash
            ? 'sync: '.substr($sourceHash, 0, 8)
            : 'sync: '.date('Y-m-d H:i:s');

        $result = Process::path($destinationPath)->run(['git', 'commit', '-m', $message]);

        if ($result->successful()) {
            $this->info("Committed in destination: {$message}");
        } else {
            $this->warn('Auto-commit failed: '.$result->errorOutput());
        }
    }
}
