<?php

namespace App\Services;

use App\Models\CatalogPdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class CatalogPdfPageManagementRenderer
{
    /**
     * Returns an absolute filesystem path to a rendered PDF.
     * For non page-management templates, returns the original file path.
     */
    public function renderPath(CatalogPdf $pdf): string
    {
        if ($pdf->template_type !== CatalogPdf::TEMPLATE_PAGE_MANAGEMENT) {
            return $pdf->storagePath();
        }

        $disk = Storage::disk($pdf->storage_disk);
        if (!$disk->exists($pdf->pdf_path)) {
            abort(404);
        }

        $pages = $pdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get();

        // If pages were not initialized, fallback to original file.
        if ($pages->isEmpty()) {
            return $pdf->storagePath();
        }

        $revision = implode('|', [
            $pdf->pdf_path,
            (string) optional($pdf->updated_at)->getTimestamp(),
            (string) ($pages->max('updated_at')?->getTimestamp() ?? 0),
            (string) $pages->count(),
        ]);

        $hash = substr(sha1($revision), 0, 16);
        $managedRelativePath = "catalog-managed/{$pdf->id}/managed-{$hash}.pdf";

        // Always store generated PDFs on local disk
        $managedDisk = Storage::disk('local');
        if ($managedDisk->exists($managedRelativePath)) {
            return $managedDisk->path($managedRelativePath);
        }

        $managedDisk->makeDirectory("catalog-managed/{$pdf->id}");
        $outputPath = $managedDisk->path($managedRelativePath);

        // 1) Try FPDI (fast). Some PDFs fail due to unsupported compression.
        try {
            $fpdi = new Fpdi();
            $pageCount = $fpdi->setSourceFile($pdf->storagePath());

            foreach ($pages as $page) {
                if ($page->page_number < 1 || $page->page_number > $pageCount) {
                    continue;
                }

                $tplId = $fpdi->importPage($page->page_number);
                $size = $fpdi->getTemplateSize($tplId);

                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplId);
            }

            $fpdi->Output($outputPath, 'F');
            return $outputPath;
        } catch (\Throwable $e) {
            Log::warning('FPDI failed to render managed PDF; attempting Ghostscript fallback.', [
                'catalog_pdf_id' => $pdf->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 2) Ghostscript fallback (more compatible). Requires Ghostscript installed on server.
        try {
            $pageList = $pages->pluck('page_number')->implode(',');

            $gsExe = $this->findGhostscriptExecutable();
            if ($gsExe) {
                $result = Process::run([
                    $gsExe,
                    '-sDEVICE=pdfwrite',
                    '-dCompatibilityLevel=1.4',
                    '-dSAFER',
                    '-dBATCH',
                    '-dNOPAUSE',
                    '-dNOPROMPT',
                    '-sOutputFile=' . $outputPath,
                    '-sPageList=' . $pageList,
                    $pdf->storagePath(),
                ]);

                if ($result->successful() && file_exists($outputPath) && filesize($outputPath) > 0) {
                    return $outputPath;
                }

                Log::warning('Ghostscript failed to render managed PDF.', [
                    'catalog_pdf_id' => $pdf->id,
                    'exit_code' => $result->exitCode(),
                    'output' => $result->output(),
                    'error_output' => $result->errorOutput(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Ghostscript fallback errored while rendering managed PDF.', [
                'catalog_pdf_id' => $pdf->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 3) Graceful fallback: return original PDF (edits won't be reflected).
        return $pdf->storagePath();
    }

    private function findGhostscriptExecutable(): ?string
    {
        // Try common executable names. On Windows it's usually gswin64c/gswin32c.
        foreach (['gswin64c', 'gswin32c', 'gs'] as $exe) {
            try {
                $result = Process::run([$exe, '-v']);
                if ($result->successful()) {
                    return $exe;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return null;
    }
}
