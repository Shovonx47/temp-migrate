<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

#[Signature('app:encrypt {--source= : Comma-separated list of source directories or files to encrypt (default: app,database,routes)}
    {--destination= : Destination directory for encrypted files (default: encrypted)}
    {--keylength= : Length of the encryption key (default: 6)}
    {--full : Force a full rebuild instead of incremental sync}
    {--force : Skip confirmation when clearing destination during full rebuild}
    {--no-commit : Skip auto-commit inside the destination repo}')]
#[Description('Encrypts specified directories and files using the bolt PHP extension. Incremental by default.')]
class EncryptApplication extends Command
{
    private const string BOLT_MARKER = '##!!!##';

    private const int DIR_PERMISSIONS = 0755;

    private const string SYNC_STATE_FILE = '.sync-state';

    /**
     * Extensions for which a warning has already been shown.
     */
    protected array $warned = [];

    public function handle(): int
    {
        if (! $this->ensureBoltLoaded()) {
            return self::FAILURE;
        }

        [$sources, $destination, $keyLength] = $this->resolveOptions();

        $destinationPath = base_path($destination);
        $currentHash = $this->gitHead(base_path());
        $syncStatePath = $destinationPath.'/'.self::SYNC_STATE_FILE;
        $lastHash = File::exists($syncStatePath) ? trim(File::get($syncStatePath)) : null;

        $needsFullRebuild = $this->option('full')
            || ! File::exists($destinationPath)
            || ! $lastHash
            || ! $currentHash
            || ! $this->isValidCommit($lastHash);

        if ($needsFullRebuild) {
            if (! $this->prepareDestination($destinationPath)) {
                return self::FAILURE;
            }

            foreach ($sources as $source) {
                if (! $this->encryptSource($source, $destination, $keyLength)) {
                    return self::FAILURE;
                }
            }
        } else {
            if ($lastHash === $currentHash) {
                $this->info('Already in sync. Nothing to do.');

                return self::SUCCESS;
            }

            $this->incrementalSync($lastHash, $sources, $destination, $keyLength);
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
     * @return array{0: array<string>, 1: string, 2: int}
     */
    private function resolveOptions(): array
    {
        $sources = $this->option('source')
            ? array_map('trim', explode(',', $this->option('source')))
            : config('encrypter.source', ['app', 'database', 'routes']);

        $destination = $this->option('destination')
            ?: config('encrypter.destination', 'encrypted');

        $keyLength = (int) ($this->option('keylength')
            ?: config('encrypter.key_length', 6));

        return [$sources, $destination, $keyLength];
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

    private function encryptSource(string $source, string $destination, int $keyLength): bool
    {
        $sourcePath = base_path($source);

        if (! File::exists($sourcePath)) {
            $this->error("Source '{$source}' does not exist.");

            return false;
        }

        File::ensureDirectoryExists(
            base_path($destination.'/'.File::dirname($source)),
            self::DIR_PERMISSIONS
        );

        if (File::isFile($sourcePath)) {
            $this->encryptFile($source, $destination, $keyLength);

            return true;
        }

        $files = $this->collectFiles($sourcePath);

        $this->info("Encrypting '{$source}'...");

        $this->withProgressBar($files, function (SplFileInfo $file) use ($destination, $keyLength) {
            $relativePath = Str::replaceFirst(base_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $this->encryptFile($relativePath, $destination, $keyLength);
        });

        $this->newLine();

        return true;
    }

    /**
     * @param  array<string>  $sources
     */
    private function incrementalSync(string $lastHash, array $sources, string $destination, int $keyLength): void
    {
        $this->deleteRemovedFiles($lastHash, $sources, $destination);
        $this->syncChangedFiles($lastHash, $sources, $destination, $keyLength);
    }

    /**
     * @param  array<string>  $sources
     */
    private function deleteRemovedFiles(string $lastHash, array $sources, string $destination): void
    {
        $result = Process::path(base_path())->run([
            'git', 'diff', '--name-only', '--diff-filter=D', "{$lastHash}..HEAD",
        ]);

        $deleted = array_filter(explode("\n", trim($result->output())));

        foreach ($deleted as $relativePath) {
            if (! $this->isWithinSources($relativePath, $sources)) {
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
     */
    private function syncChangedFiles(string $lastHash, array $sources, string $destination, int $keyLength): void
    {
        $result = Process::path(base_path())->run([
            'git', 'diff', '--name-only', '--diff-filter=ACMRT', "{$lastHash}..HEAD",
        ]);

        $files = array_filter(
            explode("\n", trim($result->output())),
            fn (string $file): bool => $file !== '' && $this->isWithinSources($file, $sources) && File::exists(base_path($file))
        );

        if (empty($files)) {
            $this->info('No source files changed.');

            return;
        }

        $this->info('Syncing '.count($files).' changed file(s)...');

        $this->withProgressBar($files, function (string $relativePath) use ($destination, $keyLength) {
            $this->encryptFile($relativePath, $destination, $keyLength);
        });

        $this->newLine();
    }

    /**
     * @param  array<string>  $sources
     */
    private function isWithinSources(string $relativePath, array $sources): bool
    {
        foreach ($sources as $source) {
            if (Str::startsWith($relativePath, $source.'/') || $relativePath === $source) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<SplFileInfo>
     */
    private function collectFiles(string $path): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = clone $file;
            }
        }

        return $files;
    }

    private function encryptFile(string $filePath, string $destination, int $keyLength): void
    {
        $sourcePath = base_path($filePath);
        $targetPath = base_path("{$destination}/{$filePath}");

        File::ensureDirectoryExists(dirname($targetPath), self::DIR_PERMISSIONS);

        if (! $this->isEncryptablePhpFile($filePath)) {
            $this->warnOnceForExtension($filePath);
            File::copy($sourcePath, $targetPath);

            return;
        }

        $contents = File::get($sourcePath);
        $contents = preg_replace('/<\?php/', '', $contents, 1);

        $key = Str::random($keyLength);
        $cipher = bolt_encrypt($contents, $key);

        if ($cipher === false) {
            $this->error("Failed to encrypt: {$filePath}");

            return;
        }

        $prepend = "<?php\nbolt_decrypt(__FILE__, '{$key}'); return 0;\n".self::BOLT_MARKER;

        File::put($targetPath, $prepend.$cipher);
    }

    private function isEncryptablePhpFile(string $filePath): bool
    {
        if (Str::endsWith($filePath, '.blade.php')) {
            return false;
        }

        return pathinfo($filePath, PATHINFO_EXTENSION) === 'php';
    }

    private function warnOnceForExtension(string $filePath): void
    {
        $extension = Str::endsWith($filePath, '.blade.php')
            ? 'blade.php'
            : pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($extension, $this->warned, true)) {
            return;
        }

        $this->warn("Encryption of .{$extension} files is not supported. These files will be copied unchanged.");
        $this->warned[] = $extension;
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
