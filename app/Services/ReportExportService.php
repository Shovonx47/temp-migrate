<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Stream a CSV file download from rows.
     */
    public static function downloadCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download a PDF report using a Blade view.
     *
     * Always returns a StreamedResponse so Livewire redirects the browser
     * to a download URL instead of trying to inline binary PDF bytes into
     * its JSON AJAX response (which causes "Malformed UTF-8 characters").
     */
    public static function downloadPdf(string $filename, string $view, array $data): StreamedResponse
    {
        $html = view($view, $data)->render();
        $pdfOutput = self::renderPdfBytes($html);

        return response()->streamDownload(function () use ($pdfOutput) {
            echo $pdfOutput;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Render HTML to raw PDF bytes, trying multiple engines in order.
     */
    private static function renderPdfBytes(string $html): string
    {
        // 1. Try Laravel service container first (barryvdh/laravel-dompdf registers 'dompdf.wrapper')
        try {
            if (app()->bound('dompdf.wrapper')) {
                /** @var PDF $pdf */
                $pdf = app('dompdf.wrapper');
                $pdf->loadHTML($html)->setPaper('a4', 'landscape');

                return $pdf->output();
            }
        } catch (\Throwable) {
            // Fall through to next approach
        }

        // 2. Try Barryvdh Facade / class directly
        $barryvdhClasses = [
            \Barryvdh\DomPDF\Facade\Pdf::class,
            PDF::class,
            'Barryvdh\DomPDF\Facade\PDF',
        ];

        foreach ($barryvdhClasses as $cls) {
            if (class_exists($cls)) {
                try {
                    if (method_exists($cls, 'loadHTML')) {
                        $pdf = $cls::loadHTML($html)->setPaper('a4', 'landscape');

                        return $pdf->output();
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        // 3. Direct Dompdf rendering (dompdf/dompdf package)
        $dompdfClass = 'Dompdf\Dompdf';
        $optionsClass = 'Dompdf\Options';

        if (class_exists($dompdfClass) && class_exists($optionsClass)) {
            try {
                $options = new $optionsClass;
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $options->set('defaultFont', 'Helvetica');
                $options->set('enableHtml5Parser', true);
                $options->set('enableRemote', true);

                $dompdf = new $dompdfClass($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('a4', 'landscape');
                $dompdf->render();

                return $dompdf->output();
            } catch (\Throwable) {
                // Last ditch: try without Options if constructor failed
                try {
                    $dompdf = new $dompdfClass;
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('a4', 'landscape');
                    $dompdf->render();

                    return $dompdf->output();
                } catch (\Throwable) {
                    // Fall through to final exception
                }
            }
        }

        throw new \RuntimeException(
            'PDF export engine is unavailable. Please ensure barryvdh/laravel-dompdf and dompdf/dompdf are installed. Run: composer require barryvdh/laravel-dompdf dompdf/dompdf && composer dump-autoload'
        );
    }
}
