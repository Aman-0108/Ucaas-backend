<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DocumentConverterService
{
    public function convertDocxToTiff($inputPath, $outputPath)
    {
        // Convert DOCX to PDF first
        $pdfPath = $outputPath . '.pdf';
        exec("libreoffice --headless --convert-to pdf --outdir " . escapeshellarg(dirname($pdfPath)) . " " . escapeshellarg($inputPath));

        // Convert PDF to TIFF
        exec("convert " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputPath));

        // Optionally remove the temporary PDF
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        return file_exists($outputPath);
    }
}
