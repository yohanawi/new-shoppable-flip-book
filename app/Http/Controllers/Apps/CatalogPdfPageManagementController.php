<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CatalogPdfPageManagementRenderer;
use App\Services\CatalogPdfSlicerImageGenerator;
use App\Services\PdfPageCounter;

class CatalogPdfPageManagementController extends Controller
{
    public function edit(CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()->get();

        return view('pages.apps.catalog.page-management', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
        ]);
    }

    public function preview(CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['page_number', 'title', 'display_order']);

        return view('pages.apps.catalog.page-preview', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf), // Use managed PDF
        ]);
    }

    public function share(CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);

        // For private PDFs, only owner can access
        if ($catalogPdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $catalogPdf->user_id !== Auth::id()) {
            abort(403);
        }

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['page_number', 'title', 'display_order']);

        return view('pages.apps.catalog.page-share', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function update(Request $request, CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $validated = $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.title' => ['nullable', 'string', 'max:255'],
            'pages.*.display_order' => ['required', 'integer', 'min:1', 'max:100000'],
            'pages.*.is_hidden' => ['nullable', 'boolean'],
            'pages.*.is_locked' => ['nullable', 'boolean'],
            'pages.*.is_deleted' => ['nullable', 'boolean'],
        ]);

        if (!$this->hasVisiblePagesAfterUpdate($catalogPdf, $validated['pages'])) {
            return redirect()
                ->back()
                ->withErrors(['pages' => 'At least one page must remain visible in the managed PDF.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $catalogPdf) {
            $existingPages = $catalogPdf->pages()->get()->keyBy('id');

            foreach ($validated['pages'] as $pageId => $payload) {
                $page = $existingPages->get((int) $pageId);
                if (!$page) {
                    continue;
                }

                // Allow toggling lock anytime
                $newIsLocked = (bool) ($payload['is_locked'] ?? false);
                $shouldDelete = (bool) ($payload['is_deleted'] ?? false);

                // If locked, do not allow title/order/hidden changes
                if ($page->is_locked && $newIsLocked) {
                    $page->is_locked = true;
                    $page->save();
                    continue;
                }

                $page->is_locked = $newIsLocked;

                if ($shouldDelete) {
                    $page->delete();
                    continue;
                }

                $page->title = $payload['title'] ?? null;
                $page->display_order = (int) $payload['display_order'];
                $page->is_hidden = (bool) ($payload['is_hidden'] ?? false);
                $page->save();
            }

            // Normalize ordering to 1..N
            $sorted = $catalogPdf->pages()->orderBy('display_order')->orderBy('id')->get();
            $order = 1;
            foreach ($sorted as $p) {
                $p->display_order = $order++;
                $p->save();
            }
        });

        $this->refreshManagedPdf($catalogPdf);

        return redirect()
            ->route('catalog.pdfs.manage', $catalogPdf)
            ->with('success', 'Pages updated successfully.');
    }

    public function initPages(Request $request, CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $validated = $request->validate([
            'page_count' => ['required', 'integer', 'min:1', 'max:2000'],
        ]);

        if ($catalogPdf->pages()->exists()) {
            return redirect()
                ->route('catalog.pdfs.manage', $catalogPdf)
                ->with('success', 'Pages already initialized.');
        }

        DB::transaction(function () use ($catalogPdf, $validated) {
            $count = (int) $validated['page_count'];

            for ($i = 1; $i <= $count; $i++) {
                $catalogPdf->pages()->create([
                    'page_number' => $i,
                    'display_order' => $i,
                    'title' => 'Page ' . $i,
                    'is_locked' => false,
                    'is_hidden' => false,
                ]);
            }
        });

        $this->refreshManagedPdf($catalogPdf);

        return redirect()
            ->route('catalog.pdfs.manage', $catalogPdf)
            ->with('success', 'Pages initialized successfully.');
    }

    public function destroyPage(CatalogPdf $catalogPdf, CatalogPdfPage $page)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        abort_unless($page->catalog_pdf_id === $catalogPdf->id, 404);
        abort_if($page->is_locked, 422, 'This page is locked.');

        $page->delete();

        // Re-normalize order after deletion
        $sorted = $catalogPdf->pages()->orderBy('display_order')->orderBy('id')->get();
        $order = 1;
        foreach ($sorted as $p) {
            $p->display_order = $order++;
            $p->save();
        }

        $this->refreshManagedPdf($catalogPdf);

        return redirect()
            ->route('catalog.pdfs.manage', $catalogPdf)
            ->with('success', 'Page deleted successfully.');
    }

    public function replacePdf(Request $request, CatalogPdf $catalogPdf)
    {
        $this->assertPageManagement($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'],
        ]);

        $file = $request->file('pdf');

        DB::transaction(function () use ($catalogPdf, $file) {
            // Replace stored file (keep same visibility behavior)
            $disk = $catalogPdf->storage_disk;

            if (Storage::disk($disk)->exists($catalogPdf->pdf_path)) {
                Storage::disk($disk)->delete($catalogPdf->pdf_path);
            }

            $path = $file->store('catalog-pdfs', $disk);

            $catalogPdf->pdf_path = $path;
            $catalogPdf->original_filename = $file->getClientOriginalName();
            $catalogPdf->mime_type = $file->getClientMimeType();
            $catalogPdf->size = $file->getSize();
            $catalogPdf->save();

            Storage::disk($disk)->deleteDirectory('catalog-slicer/' . $catalogPdf->id);
            $catalogPdf->hotspots()->delete();

            // Rebuild pages
            $catalogPdf->pages()->withTrashed()->forceDelete();

            $pageCount = app(PdfPageCounter::class)->count($catalogPdf->storagePath());
            if ($pageCount <= 0) {
                Log::warning('Unable to count pages during PDF replace; pages not initialized.', [
                    'catalog_pdf_id' => $catalogPdf->id,
                ]);
                return;
            }

            for ($i = 1; $i <= $pageCount; $i++) {
                $catalogPdf->pages()->create([
                    'page_number' => $i,
                    'display_order' => $i,
                    'title' => 'Page ' . $i,
                    'is_locked' => false,
                    'is_hidden' => false,
                ]);
            }
        });

        $this->refreshManagedPdf($catalogPdf);
        app(CatalogPdfSlicerImageGenerator::class)->generate($catalogPdf->fresh() ?? $catalogPdf);

        return redirect()
            ->route('catalog.pdfs.manage', $catalogPdf)
            ->with('success', 'PDF replaced and pages re-initialized.');
    }

    private function assertPageManagement(CatalogPdf $pdf): void
    {
        abort_unless($pdf->supportsPageManagement(), 404);
    }

    private function authorizePdfAccess(CatalogPdf $pdf): void
    {
        if (Auth::user()?->isAdmin()) {
            return;
        }

        if ($pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function refreshManagedPdf(CatalogPdf $pdf): void
    {
        try {
            $freshPdf = $pdf->fresh();
            if (!$freshPdf) {
                return;
            }

            $renderer = app(CatalogPdfPageManagementRenderer::class);
            $renderer->forgetRenderedFiles($freshPdf);
            $renderer->renderPath($freshPdf);
        } catch (\Throwable $e) {
            Log::warning('Managed PDF refresh failed after page-management update.', [
                'catalog_pdf_id' => $pdf->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasVisiblePagesAfterUpdate(CatalogPdf $pdf, array $pagesPayload): bool
    {
        $existingPages = $pdf->pages()->get()->keyBy('id');

        foreach ($pagesPayload as $pageId => $payload) {
            $page = $existingPages->get((int) $pageId);
            if (!$page) {
                continue;
            }

            $newIsLocked = (bool) ($payload['is_locked'] ?? false);
            $shouldDelete = (bool) ($payload['is_deleted'] ?? false);

            if ($page->is_locked && $newIsLocked) {
                if (!$page->is_hidden) {
                    return true;
                }

                continue;
            }

            if ($shouldDelete) {
                continue;
            }

            $willBeHidden = (bool) ($payload['is_hidden'] ?? false);
            if (!$willBeHidden) {
                return true;
            }
        }

        return false;
    }
}
