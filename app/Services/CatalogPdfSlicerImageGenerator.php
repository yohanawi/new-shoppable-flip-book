<?php

namespace App\Services;

use App\Models\CatalogPdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CatalogPdfSlicerImageGenerator
{
    /**
     * Generates page images for a PDF and stores paths on catalog_pdf_pages.
     *
     * This uses Spatie\PdfToImage (Imagick-based). If Imagick/Ghostscript are not
     * available, it will fail gracefully and leave image_path null.
     */
    public function generate(CatalogPdf $pdf, int $maxPages = 2000): void
    {
        if (!$pdf->isSlicerTemplate()) {
            return;
        }

        // Ensure pages exist (can be initialized client-side too)
        if (!$pdf->pages()->exists()) {
            return;
        }

        if (!class_exists(\Spatie\PdfToImage\Pdf::class)) {
            Log::warning('spatie/pdf-to-image not installed; cannot generate page images.', [
                'catalog_pdf_id' => $pdf->id,
            ]);
            return;
        }

        if (!extension_loaded('imagick')) {
            Log::warning('Imagick extension not loaded; cannot generate PDF images.', [
                'catalog_pdf_id' => $pdf->id,
            ]);
            return;
        }

        $absolutePdfPath = $pdf->storagePath();
        $disk = $pdf->storage_disk;

        $baseDir = 'catalog-slicer/' . $pdf->id . '/pages';

        try {
            $spatiePdf = new \Spatie\PdfToImage\Pdf($absolutePdfPath);
            // Increase DPI for better hotspot precision while keeping reasonable size
            $spatiePdf->setResolution(150);

            $pages = $pdf->pages()->orderBy('page_number')->get();
            $count = 0;

            foreach ($pages as $page) {
                if (++$count > $maxPages) {
                    break;
                }

                if ($page->image_path && Storage::disk($page->image_disk ?: $disk)->exists($page->image_path)) {
                    continue;
                }

                $filename = 'page-' . str_pad((string) $page->page_number, 4, '0', STR_PAD_LEFT) . '.jpg';
                $relativePath = $baseDir . '/' . $filename;
                $absoluteOut = Storage::disk($disk)->path($relativePath);

                // Ensure directory exists (Storage::path doesn't create directories)
                $dir = dirname($absoluteOut);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }

                // Spatie uses 1-based page numbers
                $spatiePdf->setPage($page->page_number)->saveImage($absoluteOut);

                $size = @getimagesize($absoluteOut);

                $page->image_disk = $disk;
                $page->image_path = $relativePath;
                $page->image_width = is_array($size) ? (int) ($size[0] ?? 0) : null;
                $page->image_height = is_array($size) ? (int) ($size[1] ?? 0) : null;
                $page->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to generate slicer page images; will fall back to client-side rendering.', [
                'catalog_pdf_id' => $pdf->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
