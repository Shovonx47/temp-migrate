<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
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
     */
    public static function downloadPdf(string $filename, string $view, array $data): Response
    {
        $html = view($view, $data)->render();

        // 1. If Barryvdh Facade is loaded and functioning, use it
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            try {
                /** @var \Barryvdh\DomPDF\PDF $pdf */
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');

                return $pdf->download($filename);
            } catch (\Throwable) {
                // Fallback to direct Dompdf if facade failed to resolve
            }
        }

        // 2. Direct Dompdf rendering (always bundled with dompdf/dompdf)
        if (class_exists(Dompdf::class)) {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('a4', 'landscape');
            $dompdf->render();

            return response()->make($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        throw new \RuntimeException('PDF export engine is unavailable. Please ensure dompdf is installed.');
    }
}
