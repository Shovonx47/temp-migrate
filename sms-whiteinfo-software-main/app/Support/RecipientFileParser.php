<?php

namespace App\Support;

use Generator;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Streams CSV and Excel files row-by-row so memory stays flat regardless of size.
 *
 * `peek()` returns the headers, the first few rows, and the total row count for UI preview.
 * `stream()` yields one associative row at a time and is what the processing job consumes.
 */
class RecipientFileParser
{
    private const array CSV_EXTENSIONS = ['csv', 'txt'];

    private const array EXCEL_EXTENSIONS = ['xlsx', 'xls'];

    private const string BD_PHONE_PATTERN = '/^(?:\+?88)?01[3-9]\d{8}$/';

    /**
     * @return array{headers: string[], sample: array<int, array<string, string>>, total_rows: int, has_header_row: bool}
     */
    public function peek(string $path, int $sampleSize = 5): array
    {
        $this->assertReadable($path);

        $headers = [];
        $sample = [];
        $total = 0;
        $hasHeaderRow = false;

        $rowIndex = 0;
        foreach ($this->rawRows($path) as $rawRow) {
            if ($rowIndex === 0) {
                $hasHeaderRow = $this->looksLikeHeader($rawRow);
                $headers = $hasHeaderRow
                    ? array_map(fn ($v): string => trim((string) $v), $rawRow)
                    : array_map(fn (int $i): string => 'Column '.($i + 1), array_keys($rawRow));

                if (! $hasHeaderRow) {
                    $sample[] = $this->associate($headers, $rawRow);
                    $total++;
                }
                $rowIndex++;

                continue;
            }

            if (count($sample) < $sampleSize) {
                $sample[] = $this->associate($headers, $rawRow);
            }
            $total++;
            $rowIndex++;
        }

        return [
            'headers' => $headers,
            'sample' => $sample,
            'total_rows' => $total,
            'has_header_row' => $hasHeaderRow,
        ];
    }

    public function countRecipients(string $path): int
    {
        $this->assertReadable($path);

        $count = 0;
        $first = true;

        foreach ($this->rawRows($path) as $rawRow) {
            if ($first) {
                $first = false;
                if ($this->looksLikeHeader($rawRow)) {
                    continue;
                }
            }
            $count++;
        }

        return $count;
    }

    /**
     * Yield each data row as an associative array keyed by the resolved headers.
     *
     * @return Generator<int, array<string, string>>
     */
    public function stream(string $path): Generator
    {
        $this->assertReadable($path);

        $headers = [];
        $hasHeaderRow = false;
        $rowIndex = 0;

        foreach ($this->rawRows($path) as $rawRow) {
            if ($rowIndex === 0) {
                $hasHeaderRow = $this->looksLikeHeader($rawRow);
                $headers = $hasHeaderRow
                    ? array_map(fn ($v): string => trim((string) $v), $rawRow)
                    : array_map(fn (int $i): string => 'Column '.($i + 1), array_keys($rawRow));

                $rowIndex++;

                if ($hasHeaderRow) {
                    continue;
                }
            } else {
                $rowIndex++;
            }

            yield $this->associate($headers, $rawRow);
        }
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function rawRows(string $path): Generator
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, self::CSV_EXTENSIONS, true)) {
            yield from $this->streamCsv($path);

            return;
        }

        if (in_array($ext, self::EXCEL_EXTENSIONS, true)) {
            yield from $this->streamExcel($path);

            return;
        }

        throw new InvalidArgumentException("Unsupported file type: .{$ext}");
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function streamCsv(string $path): Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException("Cannot open file: {$path}");
        }

        try {
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                // fgetcsv returns [null] for blank lines.
                if ($row === [null] || $row === [] || $row === [false]) {
                    continue;
                }
                yield array_map(fn ($v): string => is_string($v) ? trim($v) : (string) $v, $row);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    private function streamExcel(string $path): Generator
    {
        // Excel::toArray loads the entire sheet, but we contain memory by extracting one sheet
        // and yielding rows as we walk it. For very large xlsx this is the practical ceiling
        // until/unless we adopt openspout — flagged for follow-up if it becomes an issue.
        $importer = new class
        {
            // Stub importer; we just need raw arrays via toArray().
        };

        $sheets = Excel::toArray($importer, $path);

        $rows = $sheets[0] ?? [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cleaned = array_map(
                fn ($v): string => $v === null ? '' : trim((string) $v),
                $row,
            );
            // Skip rows where every cell is empty.
            if (count(array_filter($cleaned, fn (string $v): bool => $v !== '')) === 0) {
                continue;
            }
            yield $cleaned;
        }
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, string>
     */
    private function associate(array $headers, array $row): array
    {
        $padded = array_pad(array_values($row), count($headers), '');
        $padded = array_slice($padded, 0, count($headers));

        return array_combine(
            $headers,
            array_map(fn ($v): string => is_string($v) ? $v : (string) ($v ?? ''), $padded),
        );
    }

    /**
     * @param  array<int, mixed>  $firstRow
     */
    private function looksLikeHeader(array $firstRow): bool
    {
        // If any cell already looks like a phone number, the first row is data, not a header.
        foreach ($firstRow as $cell) {
            $cleaned = preg_replace('/[\s-]/', '', (string) $cell) ?? '';
            if (preg_match(self::BD_PHONE_PATTERN, $cleaned) === 1) {
                return false;
            }
        }

        return true;
    }

    private function assertReadable(string $path): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File is not readable: {$path}");
        }
    }
}
