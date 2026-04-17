<?php

namespace App\Services;

use App\Models\CatalogPdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class CatalogPdfPageManagementRenderer
{
    public function forgetRenderedFiles(CatalogPdf $pdf): void
    {
        Storage::disk('local')->deleteDirectory("catalog-managed/{$pdf->id}");
    }

    /**
     * Returns an absolute filesystem path to a rendered PDF.
     * If no page-management data exists yet, returns the original file path.
     */
    public function renderPath(CatalogPdf $pdf): string
    {
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

        $revision = [
            'pdf_path' => $pdf->pdf_path,
            'pdf_updated_at' => optional($pdf->updated_at)?->format('U.u'),
            'pages' => $pages->map(function ($page) {
                return [
                    'id' => (int) $page->id,
                    'page_number' => (int) $page->page_number,
                    'display_order' => (int) $page->display_order,
                    'title' => (string) ($page->title ?? ''),
                    'is_hidden' => (bool) $page->is_hidden,
                    'is_locked' => (bool) $page->is_locked,
                    'updated_at' => optional($page->updated_at)?->format('U.u'),
                ];
            })->values()->all(),
        ];

        $hash = substr(sha1(json_encode($revision, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 16);
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

                if (filled($page->title)) {
                    $fpdi->Bookmark($page->title, 0, 0);
                }
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
