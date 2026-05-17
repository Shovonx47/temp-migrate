<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Propaganistas\LaravelPhone\PhoneNumber;

class BenchmarkJob implements ShouldQueue
{
    use Queueable;

    private const SAMPLE_SIZE = 100_000;
    private const WARMUP_SIZE = 1_000;

    public function handle(): void
    {
        $numbers = $this->generateTestData(self::SAMPLE_SIZE);

        ray('=== Benchmark Starting ===')->purple();
        ray([
            'sample_size' => self::SAMPLE_SIZE,
            'xdebug' => extension_loaded('xdebug') ? 'ON ⚠️' : 'off',
            'opcache' => function_exists('opcache_get_status') && @opcache_get_status() ? 'on' : 'off',
            'php_version' => PHP_VERSION,
        ])->blue();

        // Warmup all functions
        $this->warmup($numbers);

        // Run benchmarks
        $results = [
            'fast_path' => $this->benchmark('Fast Path (custom)', $numbers, fn ($n) => $this->formatFast($n)),
            'original' => $this->benchmark('Original (digit loop)', $numbers, fn ($n) => $this->formatOriginal($n)),
            'regex' => $this->benchmark('Regex based', $numbers, fn ($n) => $this->formatRegex($n)),
            'libphone' => $this->benchmark('Propaganistas/LaravelPhone', $numbers, fn ($n) => $this->formatLibPhone($n)),
        ];

        ray('=== Results ===')->purple();
        ray($results)->green();

        // Sanity check: all functions should agree on valid results
        $this->verifyConsistency($numbers);
    }

    /**
     * Generate realistic mixed test data.
     */
    private function generateTestData(int $count): array
    {
        $numbers = [];
        $prefixes = ['013', '014', '015', '016', '017', '018', '019'];

        for ($i = 0; $i < $count; $i++) {
            $prefix = $prefixes[$i % 7];
            $suffix = str_pad((string) $i, 8, '0', STR_PAD_LEFT);

            // Realistic distribution
            $numbers[] = match ($i % 10) {
                0, 1, 2, 3, 4, 5 => '0' . $prefix . $suffix,            // 60% - clean 01XXXXXXXXX
                6, 7 => '+880' . $prefix . $suffix,                      // 20% - +8801XXXXXXXXX
                8 => '880' . $prefix . $suffix,                          // 10% - 8801XXXXXXXXX
                9 => $prefix . $suffix,                                  // 10% - 1XXXXXXXXX (no leading 0)
            };
        }

        return $numbers;
    }

    /**
     * Warmup OPcache + branch predictor.
     */
    private function warmup(array $numbers): void
    {
        $sample = array_slice($numbers, 0, self::WARMUP_SIZE);

        foreach ($sample as $n) {
            $this->formatFast($n);
            $this->formatOriginal($n);
            $this->formatRegex($n);
            $this->formatLibPhone($n);
        }
    }

    /**
     * Run a single benchmark with proper timing.
     */
    private function benchmark(string $label, array $numbers, callable $fn): array
    {
        $count = count($numbers);
        $valid = 0;

        $startNs = hrtime(true);
        foreach ($numbers as $n) {
            if ($fn($n) !== null) {
                $valid++;
            }
        }
        $elapsedNs = hrtime(true) - $startNs;

        return [
            'label' => $label,
            'total_ms' => round($elapsedNs / 1_000_000, 2),
            'per_call_us' => round($elapsedNs / 1000 / $count, 3),
            'calls_per_sec' => number_format((int) round($count / ($elapsedNs / 1e9))),
            'valid_count' => $valid,
            'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    /**
     * Verify all implementations produce same output for sample inputs.
     */
    private function verifyConsistency(array $numbers): void
    {
        $sample = array_slice($numbers, 0, 20);
        $mismatches = [];

        foreach ($sample as $n) {
            $fast = $this->formatFast($n);
            $original = $this->formatOriginal($n);
            $regex = $this->formatRegex($n);

            if ($fast !== $original || $fast !== $regex) {
                $mismatches[] = compact('n', 'fast', 'original', 'regex');
            }
        }

        if (! empty($mismatches)) {
            ray('⚠️ Mismatches found between implementations!')->red();
            ray($mismatches)->red();
        } else {
            ray('✅ All implementations agree on sample inputs')->green();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Implementation 1: Fast Path (optimized)
    |--------------------------------------------------------------------------
    */
    private function formatFast(string $number): ?string
    {
        $len = strlen($number);

        // "01XXXXXXXXX" (11 chars)
        if ($len === 11) {
            if ($number[0] === '0' && $number[1] === '1'
                && $number[2] >= '3' && $number[2] <= '9'
                && ctype_digit($number)) {
                return '+88' . $number;
            }
            return null;
        }

        // "1XXXXXXXXX" (10 chars, no leading 0)
        if ($len === 10) {
            if ($number[0] === '1'
                && $number[1] >= '3' && $number[1] <= '9'
                && ctype_digit($number)) {
                return '+880' . $number;
            }
            return null;
        }

        // "8801XXXXXXXXX" (13 chars)
        if ($len === 13) {
            if ($number[0] === '8' && $number[1] === '8' && $number[2] === '0'
                && $number[3] === '1' && $number[4] >= '3' && $number[4] <= '9'
                && ctype_digit($number)) {
                return '+' . $number;
            }
            return null;
        }

        // "+8801XXXXXXXXX" (14 chars)
        if ($len === 14 && $number[0] === '+') {
            if ($number[1] === '8' && $number[2] === '8' && $number[3] === '0'
                && $number[4] === '1' && $number[5] >= '3' && $number[5] <= '9'
                && ctype_digit(substr($number, 1))) {
                return $number;
            }
            return null;
        }

        // Slow path: digit extraction
        return $this->formatSlow($number);
    }

    private function formatSlow(string $number): ?string
    {
        $digits = '';
        $len = strlen($number);

        for ($i = 0; $i < $len; $i++) {
            $c = $number[$i];
            if ($c >= '0' && $c <= '9') {
                $digits .= $c;
                if (strlen($digits) > 13) return null;
            }
        }

        $dlen = strlen($digits);

        if ($dlen === 13 && $digits[0] === '8' && $digits[1] === '8' && $digits[2] === '0') {
            $digits = '0' . substr($digits, 3);
        } elseif ($dlen === 10 && $digits[0] === '1') {
            $digits = '0' . $digits;
        } elseif ($dlen !== 11) {
            return null;
        }

        if ($digits[0] !== '0' || $digits[1] !== '1'
            || $digits[2] < '3' || $digits[2] > '9') {
            return null;
        }

        return '+88' . $digits;
    }

    /*
    |--------------------------------------------------------------------------
    | Implementation 2: Original (char-by-char digit extraction)
    |--------------------------------------------------------------------------
    */
    private function formatOriginal(string $number): ?string
    {
        $digits = '';
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $char = $number[$i];
            if ($char >= '0' && $char <= '9') {
                $digits .= $char;
            }
        }

        $length = strlen($digits);

        if ($length === 13 && strncmp($digits, '880', 3) === 0) {
            $digits = '0' . substr($digits, 3);
            $length = 11;
        } elseif ($length === 10 && $digits[0] === '1') {
            $digits = '0' . $digits;
            $length = 11;
        }

        if ($length !== 11) {
            return null;
        }

        if ($digits[0] !== '0' || $digits[1] !== '1') {
            return null;
        }

        $operator = $digits[2];
        if ($operator < '3' || $operator > '9') {
            return null;
        }

        return '+88' . $digits;
    }

    /*
    |--------------------------------------------------------------------------
    | Implementation 3: Regex
    |--------------------------------------------------------------------------
    */
    private function formatRegex(string $number): ?string
    {
        if (preg_match('/^(?:\+?88)?0?(1[3-9]\d{8})$/', $number, $m)) {
            return '+880' . $m[1];
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Implementation 4: Propaganistas/LaravelPhone (libphonenumber)
    |--------------------------------------------------------------------------
    */
    private function formatLibPhone(string $number): ?string
    {
        try {
            $phone = new PhoneNumber($number, 'BD');
            if (! $phone->isValid()) {
                return null;
            }
            return $phone->formatE164();
        } catch (\Throwable) {
            return null;
        }
    }
}
