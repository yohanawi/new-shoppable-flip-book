<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfFlipPhysicsSetting;
use App\Models\CatalogPdfHotspot;
use App\Services\BillingManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CatalogPdfPageManagementRenderer;
use App\Services\CatalogPdfSlicerImageGenerator;
use App\Services\PdfPageCounter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogPdfController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'visibility' => $request->query('visibility'),
            'template_type' => $request->query('template_type'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $visibilityOptions = CatalogPdf::visibilityOptions();
        $templateTypeOptions = CatalogPdf::templateTypeOptions();
        $sortOptions = [
            'latest' => 'Newest first',
            'oldest' => 'Oldest first',
            'title_asc' => 'Title A-Z',
            'title_desc' => 'Title Z-A',
        ];

        if (!array_key_exists($filters['visibility'], $visibilityOptions)) {
            $filters['visibility'] = null;
        }

        if (!array_key_exists($filters['template_type'], $templateTypeOptions)) {
            $filters['template_type'] = null;
        }

        if (!array_key_exists($filters['sort'], $sortOptions)) {
            $filters['sort'] = 'latest';
        }

        $baseQuery = CatalogPdf::query()
            ->with('user')
            ->when(!$this->currentUserIsAdmin(), fn(Builder $query) => $query->where('user_id', Auth::id()));

        $stats = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN visibility = ? THEN 1 ELSE 0 END) as public_count", [CatalogPdf::VISIBILITY_PUBLIC])
            ->selectRaw("SUM(CASE WHEN visibility = ? THEN 1 ELSE 0 END) as private_count", [CatalogPdf::VISIBILITY_PRIVATE])
            ->selectRaw("SUM(CASE WHEN template_type = ? THEN 1 ELSE 0 END) as uploaded_count", [CatalogPdf::TEMPLATE_UPLOADED])
            ->first();

        $pdfs = (clone $baseQuery)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']) . '%';

                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('title', 'like', $search)
                        ->orWhere('original_filename', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($filters['visibility'], fn(Builder $query, string $visibility) => $query->where('visibility', $visibility))
            ->when($filters['template_type'], fn(Builder $query, string $templateType) => $query->where('template_type', $templateType));

        match ($filters['sort']) {
            'oldest' => $pdfs->oldest(),
            'title_asc' => $pdfs->orderBy('title'),
            'title_desc' => $pdfs->orderByDesc('title'),
            default => $pdfs->latest(),
        };

        $pdfs = $pdfs
            ->paginate(12)
            ->withQueryString();

        return view('pages.apps.catalog.index', [
            'pdfs' => $pdfs,
            'filters' => $filters,
            'visibilityOptions' => $visibilityOptions,
            'templateTypeOptions' => $templateTypeOptions,
            'sortOptions' => $sortOptions,
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'public' => (int) ($stats->public_count ?? 0),
                'private' => (int) ($stats->private_count ?? 0),
                'uploaded' => (int) ($stats->uploaded_count ?? 0),
            ],
        ]);
    }

    public function create()
    {
        $visibilityOptions = CatalogPdf::visibilityOptions();

        return view('pages.apps.catalog.create', compact('visibilityOptions'));
    }

    public function store(Request $request, BillingManager $billingManager)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'template_type' => ['nullable', 'string', Rule::in(array_keys(CatalogPdf::workflowTypeOptions()))],
            'visibility' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdf::visibilityOptions()))],
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'], // 20MB
        ]);

        $file = $request->file('pdf');
        $billingCheck = $billingManager->canCreateFlipbook($request->user(), $file?->getSize() ?? 0);

        if (!$billingCheck['allowed']) {
            return redirect()
                ->back()
                ->withErrors(['billing' => $billingCheck['message']])
                ->withInput();
        }

        $disk = $validated['visibility'] === CatalogPdf::VISIBILITY_PUBLIC ? 'public' : 'local';
        $templateType = $validated['template_type'] ?? CatalogPdf::defaultTemplateType();

        $path = $file->store('catalog-pdfs', $disk);

        $pdf = CatalogPdf::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'template_type' => $templateType,
            'visibility' => $validated['visibility'],
            'storage_disk' => $disk,
            'pdf_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $this->prepareWorkflowAssets($pdf);

        if ($pdf->isUploadedTemplate()) {
            return redirect()
                ->route('catalog.pdfs.show', $pdf)
                ->with('success', 'PDF uploaded successfully. Choose a workflow to continue.');
        }

        return redirect()
            ->route('catalog.pdfs.index')
            ->with('success', 'PDF uploaded successfully.');
    }

    public function selectWorkflow(Request $request, CatalogPdf $catalogPdf)
    {
        $this->authorizeManagementAccess($catalogPdf);

        $validated = $request->validate([
            'template_type' => ['required', 'string', Rule::in(array_keys(CatalogPdf::workflowTypeOptions()))],
        ]);

        $catalogPdf->template_type = $validated['template_type'];
        $catalogPdf->save();

        $this->prepareWorkflowAssets($catalogPdf);

        return redirect()
            ->to($this->workflowUrlFor($catalogPdf))
            ->with('success', 'Workflow selected successfully.');
    }

    public function show(CatalogPdf $catalogPdf)
    {
        $this->authorizeManagementAccess($catalogPdf);

        return view('pages.apps.catalog.show', [
            'pdf' => $catalogPdf,
            'templateTypes' => CatalogPdf::templateTypeOptions(),
        ]);
    }

    public function share(CatalogPdf $catalogPdf)
    {
        $this->authorizeViewerAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get()
            ->map(fn(CatalogPdfHotspot $hotspot) => $this->serializeSharedHotspot($catalogPdf, $hotspot))
            ->values();

        $setting = $catalogPdf->flipPhysicsSetting()->first();
        if (!$setting) {
            $setting = new CatalogPdfFlipPhysicsSetting([
                'catalog_pdf_id' => $catalogPdf->id,
            ]);
            $setting->applyPreset(CatalogPdfFlipPhysicsSetting::defaultPreset());
        }

        return view('pages.apps.catalog.share', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'viewerSettings' => $setting->viewerSettings(),
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function file(CatalogPdf $catalogPdf): BinaryFileResponse
    {
        $this->authorizeViewerAccess($catalogPdf);
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
        $this->authorizeManagementAccess($catalogPdf);
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
        $this->authorizeViewerAccess($catalogPdf);
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
        $this->authorizeManagementAccess($catalogPdf);

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

    private function authorizeManagementAccess(CatalogPdf $pdf): void
    {
        if ($this->currentUserIsAdmin()) {
            return;
        }

        if ($pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function authorizeViewerAccess(CatalogPdf $pdf): void
    {
        if ($this->currentUserIsAdmin()) {
            return;
        }

        if ($pdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function currentUserIsAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
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

    private function prepareWorkflowAssets(CatalogPdf $pdf): void
    {
        if ($pdf->isPageManagementTemplate() || $pdf->isSlicerTemplate()) {
            $this->initializePagesIfNeeded($pdf);
        }

        if ($pdf->isSlicerTemplate()) {
            app(CatalogPdfSlicerImageGenerator::class)->generate($pdf);
        }
    }

    private function workflowUrlFor(CatalogPdf $pdf): string
    {
        if ($pdf->isPageManagementTemplate()) {
            return route('catalog.pdfs.manage', $pdf);
        }

        if ($pdf->isFlipPhysicsTemplate()) {
            return route('catalog.pdfs.flip-physics.edit', $pdf);
        }

        if ($pdf->isSlicerTemplate()) {
            return route('catalog.pdfs.slicer.edit', $pdf);
        }

        return route('catalog.pdfs.show', $pdf);
    }

    private function serializeSharedHotspot(CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot): array
    {
        return [
            'id' => (int) $hotspot->id,
            'catalog_pdf_id' => (int) $hotspot->catalog_pdf_id,
            'catalog_pdf_page_id' => (int) $hotspot->catalog_pdf_page_id,
            'display_order' => (int) $hotspot->display_order,
            'shape_type' => (string) $hotspot->shape_type,
            'shape_data' => $hotspot->shape_data,
            'x' => (float) $hotspot->x,
            'y' => (float) $hotspot->y,
            'w' => (float) $hotspot->w,
            'h' => (float) $hotspot->h,
            'action_type' => (string) $hotspot->action_type,
            'is_active' => (bool) $hotspot->is_active,
            'title' => $hotspot->title,
            'color' => $hotspot->color,
            'link' => $hotspot->link,
            'internal_page_number' => $hotspot->internal_page_number,
            'description' => $hotspot->description,
            'price' => $hotspot->price,
            'thumbnail_path' => $hotspot->thumbnail_path,
            'thumbnail_url' => $hotspot->thumbnail_path
                ? route('catalog.pdfs.slicer.hotspots.media', [$catalogPdf, $hotspot, 'thumbnail'])
                : null,
            'popup_image_path' => $hotspot->popup_image_path,
            'popup_image_url' => $hotspot->popup_image_path
                ? route('catalog.pdfs.slicer.hotspots.media', [$catalogPdf, $hotspot, 'popup_image'])
                : null,
            'popup_video_path' => $hotspot->popup_video_path,
            'popup_video_file_url' => $hotspot->popup_video_path
                ? route('catalog.pdfs.slicer.hotspots.media', [$catalogPdf, $hotspot, 'popup_video'])
                : null,
            'popup_video_url' => $hotspot->popup_video_url,
        ];
    }
}
