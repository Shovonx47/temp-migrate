<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class FileParser
{
    /**
     * Extract {variable} placeholders from a message template.
     */
    public static function extractVariables(?string $body): array
    {
        if (empty($body)) {
            return [];
        }

        preg_match_all('/\{(\w+)\}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Parse uploaded file. Returns headers, preview rows, and full row count.
     */
    public static function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = match ($extension) {
            'csv', 'txt' => self::parseCsv($path),
            'xls', 'xlsx' => self::parseExcel($path),
            default => [],
        };

        if (empty($rows)) {
            return ['headers' => [], 'preview' => [], 'all_rows' => [], 'total' => 0];
        }

        // Detect if first row looks like a header
        $firstRow = $rows[0];
        $hasHeader = self::looksLikeHeader($firstRow);

        if ($hasHeader) {
            $headers = $firstRow;
            $dataRows = array_slice($rows, 1);
        } else {
            $headers = array_map(fn ($i) => 'Column ' . chr(65 + $i), array_keys($firstRow));
            $dataRows = $rows;
        }

        return [
            'headers' => $headers,
            'preview' => array_slice($dataRows, 0, 5),
            'all_rows' => $dataRows,
            'total' => count($dataRows),
        ];
    }

    /**
     * Apply column mapping to extract recipients with variable values.
     */
    public static function applyMapping(array $allRows, array $mapping): array
    {
        $phoneColumnIndex = $mapping['phone'] ?? null;

        if ($phoneColumnIndex === null) {
            return [];
        }

        $variableMap = $mapping['variables'] ?? [];
        $recipients = [];

        foreach ($allRows as $row) {
            $rawPhone = $row[$phoneColumnIndex] ?? null;
            $phone = self::formatPhone($rawPhone);

            if (! $phone) {
                continue;
            }

            $variables = [];
            foreach ($variableMap as $varName => $columnIndex) {
                if ($columnIndex !== null && isset($row[$columnIndex])) {
                    $variables[$varName] = trim((string) $row[$columnIndex]);
                }
            }

            $recipients[] = [
                'phone' => $phone,
                'variables' => $variables,
            ];
        }

        // Deduplicate by phone, keep first occurrence
        return collect($recipients)
            ->unique('phone')
            ->values()
            ->all();
    }

    public static function formatPhone(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        try {
            $phone = new PhoneNumber(trim($number), 'BD');

            return $phone->isValid() ? $phone->formatE164() : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function parseCsv(string $path): array
    {
        $rows = [];

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        return $rows;
    }

    protected static function parseExcel(string $path): array
    {
        $sheets = Excel::toArray([], $path);

        return $sheets[0] ?? [];
    }

    /**
     * A row is treated as a header if no cell looks like a valid phone number.
     */
    protected static function looksLikeHeader(array $row): bool
    {
        foreach ($row as $cell) {
            if (self::formatPhone($cell) !== null) {
                return false;
            }
        }

        return true;
    }
}
