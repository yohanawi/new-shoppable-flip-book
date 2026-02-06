<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CatalogPdfPageManagementRenderer;
use App\Services\CatalogPdfSlicerImageGenerator;
use App\Services\PdfPageCounter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogPdfController extends Controller
{
    public function index()
    {
        $pdfs = CatalogPdf::query()
            ->latest()
            ->paginate(12);

        return view('pages.apps.catalog.index', compact('pdfs'));
    }

    public function create()
    {
        $templateTypes = CatalogPdf::templateTypeOptions();
        $visibilityOptions = CatalogPdf::visibilityOptions();

        return view('pages.apps.catalog.create', compact('templateTypes', 'visibilityOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'template_type' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdf::templateTypeOptions()))],
            'visibility' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdf::visibilityOptions()))],
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'], // 20MB
        ]);

        $disk = $validated['visibility'] === CatalogPdf::VISIBILITY_PUBLIC ? 'public' : 'local';

        $file = $request->file('pdf');
        $path = $file->store('catalog-pdfs', $disk);

        $pdf = CatalogPdf::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'template_type' => $validated['template_type'],
            'visibility' => $validated['visibility'],
            'storage_disk' => $disk,
            'pdf_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        if ($pdf->isPageManagementTemplate()) {
            $this->initializePagesIfNeeded($pdf);
        }

        if ($pdf->isSlicerTemplate()) {
            $this->initializePagesIfNeeded($pdf);
            app(CatalogPdfSlicerImageGenerator::class)->generate($pdf);
        }

        return redirect()
            ->route('catalog.pdfs.index')
            ->with('success', 'PDF uploaded successfully.');
    }

    public function show(CatalogPdf $catalogPdf)
    {
        $this->authorizePdfAccess($catalogPdf);

        return view('pages.apps.catalog.show', [
            'pdf' => $catalogPdf,
            'templateTypes' => CatalogPdf::templateTypeOptions(),
        ]);
    }

    public function file(CatalogPdf $catalogPdf): BinaryFileResponse
    {
        $this->authorizePdfAccess($catalogPdf);
        abort_unless(Storage::disk($catalogPdf->storage_disk)->exists($catalogPdf->pdf_path), 404);

        if ($catalogPdf->isPageManagementTemplate()) {
            $this->initializePagesIfNeeded($catalogPdf);
        }

        $path = app(CatalogPdfPageManagementRenderer::class)->renderPath($catalogPdf);

        return response()->file(
            $path,
            [
                'Content-Type' => $catalogPdf->mime_type ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($catalogPdf->original_filename ?: 'document.pdf') . '"',
            ]
        );
    }

    public function source(CatalogPdf $catalogPdf): BinaryFileResponse
    {
        // Always stream the originally uploaded PDF (no page-management rendering)
        $this->authorizePdfAccess($catalogPdf);
        abort_unless(Storage::disk($catalogPdf->storage_disk)->exists($catalogPdf->pdf_path), 404);

        return response()->file(
            $catalogPdf->storagePath(),
            [
                'Content-Type' => $catalogPdf->mime_type ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($catalogPdf->original_filename ?: 'document.pdf') . '"',
            ]
        );
    }

    public function download(CatalogPdf $catalogPdf): BinaryFileResponse
    {
        $this->authorizePdfAccess($catalogPdf);
        abort_unless(Storage::disk($catalogPdf->storage_disk)->exists($catalogPdf->pdf_path), 404);

        if ($catalogPdf->isPageManagementTemplate()) {
            $this->initializePagesIfNeeded($catalogPdf);
        }

        $path = app(CatalogPdfPageManagementRenderer::class)->renderPath($catalogPdf);

        return response()->download(
            $path,
            $catalogPdf->original_filename ?: 'document.pdf',
            [
                'Content-Type' => $catalogPdf->mime_type ?: 'application/pdf',
            ]
        );
    }

    public function destroy(CatalogPdf $catalogPdf)
    {
        $this->authorizePdfAccess($catalogPdf);

        // Delete stored PDF file
        if (Storage::disk($catalogPdf->storage_disk)->exists($catalogPdf->pdf_path)) {
            Storage::disk($catalogPdf->storage_disk)->delete($catalogPdf->pdf_path);
        }

        // Delete managed files (if any)
        if ($catalogPdf->isPageManagementTemplate()) {
            $managedDir = "catalog-managed/{$catalogPdf->id}";
            if (Storage::disk('local')->exists($managedDir)) {
                Storage::disk('local')->deleteDirectory($managedDir);
            }
        }

        // Delete database record (cascade deletes pages and hotspots)
        $catalogPdf->delete();

        return redirect()
            ->route('catalog.pdfs.index')
            ->with('success', 'PDF deleted successfully.');
    }

    private function authorizePdfAccess(CatalogPdf $pdf): void
    {
        if ($pdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function initializePagesIfNeeded(CatalogPdf $pdf): void
    {
        if (!($pdf->isPageManagementTemplate() || $pdf->isSlicerTemplate())) {
            return;
        }

        if ($pdf->pages()->exists()) {
            return;
        }

        try {
            $pageCount = app(PdfPageCounter::class)->count($pdf->storagePath());
        } catch (\Throwable $e) {
            Log::warning('Page initialization failed; PDF will still display but page-management edits may be unavailable.', [
                'catalog_pdf_id' => $pdf->id,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if ($pageCount <= 0) {
            Log::warning('Unable to determine PDF page count (FPDI + GS failed).', [
                'catalog_pdf_id' => $pdf->id,
            ]);
            return;
        }

        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->pages()->create([
                'page_number' => $i,
                'display_order' => $i,
                'title' => 'Page ' . $i,
                'is_locked' => false,
                'is_hidden' => false,
            ]);
        }
    }
}
