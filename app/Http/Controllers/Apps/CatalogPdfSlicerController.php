<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\CatalogPdfHotspot;
use App\Models\CatalogPdfPage;
use App\Services\CatalogPdfSlicerImageGenerator;
use App\Services\Notifications\CatalogPdfMilestoneNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogPdfSlicerController extends Controller
{
    public function edit(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        return view('pages.apps.catalog.slicer-editor', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
            'shapeOptions' => CatalogPdfHotspot::shapeOptions(),
            'actionOptions' => CatalogPdfHotspot::actionOptions(),
        ]);
    }

    public function preview(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get()
            ->map(fn(CatalogPdfHotspot $hotspot) => $this->serializeHotspot($catalogPdf, $hotspot))
            ->values();

        return view('pages.apps.catalog.slicer-preview', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function live(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get()
            ->map(fn(CatalogPdfHotspot $hotspot) => $this->serializeHotspot($catalogPdf, $hotspot))
            ->values();

        return view('pages.apps.catalog.slicer-live', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function share(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);

        if (
            !$this->currentUserIsAdmin()
            && $catalogPdf->visibility === CatalogPdf::VISIBILITY_PRIVATE
            && $catalogPdf->user_id !== Auth::id()
        ) {
            abort(403);
        }

        $pages = $catalogPdf->pages()
            ->where('is_hidden', false)
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get()
            ->map(fn(CatalogPdfHotspot $hotspot) => $this->serializeHotspot($catalogPdf, $hotspot))
            ->values();

        return view('pages.apps.catalog.slicer-share', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function initPages(Request $request, CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $validated = $request->validate([
            'page_count' => ['required', 'integer', 'min:1', 'max:2000'],
        ]);

        if ($catalogPdf->pages()->exists()) {
            return redirect()
                ->route('catalog.pdfs.slicer.edit', $catalogPdf)
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

        return redirect()
            ->route('catalog.pdfs.slicer.edit', $catalogPdf)
            ->with('success', 'Pages initialized successfully.');
    }

    public function generateImages(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        app(CatalogPdfSlicerImageGenerator::class)->generate($catalogPdf);

        return redirect()
            ->route('catalog.pdfs.slicer.edit', $catalogPdf)
            ->with('success', 'Image generation started (or skipped if already generated).');
    }

    public function pageImage(CatalogPdf $catalogPdf, CatalogPdfPage $page): BinaryFileResponse
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizeViewer($catalogPdf);
        abort_unless($page->catalog_pdf_id === $catalogPdf->id, 404);

        $disk = $page->image_disk ?: $catalogPdf->storage_disk;
        abort_unless($page->image_path && Storage::disk($disk)->exists($page->image_path), 404);

        return response()->file(Storage::disk($disk)->path($page->image_path));
    }

    public function hotspotsForPage(CatalogPdf $catalogPdf, CatalogPdfPage $page)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);
        abort_unless($page->catalog_pdf_id === $catalogPdf->id, 404);

        $items = $catalogPdf->hotspots()
            ->where('catalog_pdf_page_id', $page->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn(CatalogPdfHotspot $hotspot) => $this->serializeHotspot($catalogPdf, $hotspot))
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function storeHotspot(Request $request, CatalogPdf $catalogPdf, CatalogPdfPage $page)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);
        abort_unless($page->catalog_pdf_id === $catalogPdf->id, 404);

        $validated = $this->validateHotspot($request);
        if (empty($validated['display_order'])) {
            $validated['display_order'] = (int) $catalogPdf->hotspots()
                ->where('catalog_pdf_page_id', $page->id)
                ->max('display_order') + 1;
        }

        $disk = $catalogPdf->storage_disk;

        $hotspot = DB::transaction(function () use ($validated, $catalogPdf, $page, $request, $disk) {
            $hotspot = CatalogPdfHotspot::create(array_merge($validated, [
                'catalog_pdf_id' => $catalogPdf->id,
                'catalog_pdf_page_id' => $page->id,
            ]));

            $this->handleHotspotUploads($request, $hotspot, $disk);

            return $hotspot->fresh();
        });

        return response()->json([
            'data' => $this->serializeHotspot($catalogPdf, $hotspot),
        ], 201);
    }

    public function updateHotspot(Request $request, CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);
        abort_unless($hotspot->catalog_pdf_id === $catalogPdf->id, 404);

        $validated = $this->validateHotspot($request, isUpdate: true, existingHotspot: $hotspot);
        if (empty($validated['display_order'])) {
            $validated['display_order'] = $hotspot->display_order;
        }
        $disk = $catalogPdf->storage_disk;

        $hotspot = DB::transaction(function () use ($validated, $request, $hotspot, $disk) {
            $hotspot->fill($validated);
            $this->syncHotspotMediaForAction($hotspot);
            $hotspot->save();

            $this->handleHotspotUploads($request, $hotspot, $disk);

            return $hotspot->fresh();
        });

        return response()->json([
            'data' => $this->serializeHotspot($catalogPdf, $hotspot),
        ]);
    }

    public function destroyHotspot(CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);
        abort_unless($hotspot->catalog_pdf_id === $catalogPdf->id, 404);

        $this->deleteHotspotMedia($hotspot);
        $hotspot->delete();

        return response()->json(['ok' => true]);
    }

    public function hotspotMedia(CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot, string $kind): BinaryFileResponse
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizeViewer($catalogPdf);
        abort_unless($hotspot->catalog_pdf_id === $catalogPdf->id, 404);

        $disk = null;
        $path = null;

        if ($kind === 'thumbnail') {
            $disk = $hotspot->thumbnail_disk;
            $path = $hotspot->thumbnail_path;
        } elseif ($kind === 'popup_image') {
            $disk = $hotspot->popup_image_disk;
            $path = $hotspot->popup_image_path;
        } elseif ($kind === 'popup_video') {
            $disk = $hotspot->popup_video_disk;
            $path = $hotspot->popup_video_path;
        } else {
            abort(404);
        }

        abort_unless($disk && $path && Storage::disk($disk)->exists($path), 404);

        return response()->file(Storage::disk($disk)->path($path));
    }

    public function track(Request $request, CatalogPdf $catalogPdf, CatalogPdfMilestoneNotificationService $milestoneNotificationService)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'max:50'],
            'page_number' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'hotspot_id' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ]);

        $hotspotId = $validated['hotspot_id'] ?? null;
        if ($hotspotId) {
            $exists = CatalogPdfHotspot::query()
                ->where('id', $hotspotId)
                ->where('catalog_pdf_id', $catalogPdf->id)
                ->exists();
            if (!$exists) {
                $hotspotId = null;
            }
        }

        $event = CatalogPdfEvent::create([
            'catalog_pdf_id' => $catalogPdf->id,
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'event_type' => $validated['event_type'],
            'page_number' => $validated['page_number'] ?? null,
            'catalog_pdf_hotspot_id' => $hotspotId,
            'meta' => $validated['meta'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'created_at' => now(),
        ]);

        $milestoneNotificationService->handleTrackedEvent($catalogPdf, $event->event_type);

        return response()->json(['ok' => true]);
    }

    private function validateHotspot(Request $request, bool $isUpdate = false, ?CatalogPdfHotspot $existingHotspot = null): array
    {
        // HTML checkbox submits "on" when checked and omits the key when unchecked.
        // Normalize to a real boolean so the validator doesn't reject it.
        $request->merge([
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        $rules = [
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'shape_type' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdfHotspot::shapeOptions()))],
            'shape_data' => ['required'],
            'x' => ['required', 'numeric', 'min:0', 'max:1'],
            'y' => ['required', 'numeric', 'min:0', 'max:1'],
            'w' => ['required', 'numeric', 'min:0', 'max:1'],
            'h' => ['required', 'numeric', 'min:0', 'max:1'],
            'action_type' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdfHotspot::actionOptions()))],
            'is_active' => ['nullable', 'boolean'],

            'title' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'link' => ['nullable', 'string', 'max:2048'],
            'internal_page_number' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],

            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'popup_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'popup_video' => ['nullable', 'file', 'mimes:mp4,webm', 'max:51200'],
            'popup_video_url' => ['nullable', 'string', 'max:2048'],
        ];

        $validated = $request->validate($rules);

        if (is_string($validated['shape_data'])) {
            $decoded = json_decode($validated['shape_data'], true);
            if (!is_array($decoded)) {
                abort(422, 'Invalid shape data.');
            }
            $validated['shape_data'] = $decoded;
        }

        if (!is_array($validated['shape_data'])) {
            abort(422, 'Invalid shape data.');
        }

        $errors = [];

        if (($validated['action_type'] ?? null) === CatalogPdfHotspot::ACTION_INTERNAL_PAGE
            && empty($validated['internal_page_number'])
        ) {
            $errors['internal_page_number'] = 'Select the internal page to open.';
        }

        if (($validated['action_type'] ?? null) === CatalogPdfHotspot::ACTION_EXTERNAL_LINK
            && blank($validated['link'] ?? null)
        ) {
            $errors['link'] = 'Enter the external URL.';
        }

        if (($validated['action_type'] ?? null) === CatalogPdfHotspot::ACTION_POPUP_IMAGE
            && !$request->hasFile('popup_image')
            && blank($existingHotspot?->popup_image_path)
        ) {
            $errors['popup_image'] = 'Upload the popup image.';
        }

        $hasPopupVideoSource = $request->hasFile('popup_video')
            || filled($validated['popup_video_url'] ?? null)
            || filled($existingHotspot?->popup_video_path);

        if (($validated['action_type'] ?? null) === CatalogPdfHotspot::ACTION_POPUP_VIDEO
            && !$hasPopupVideoSource
        ) {
            $errors['popup_video'] = 'Upload a popup video file or provide a popup video URL.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        // Normalize booleans
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['display_order'] = isset($validated['display_order'])
            ? (int) $validated['display_order']
            : null;
        $validated['title'] = filled($validated['title'] ?? null) ? trim((string) $validated['title']) : null;
        $validated['color'] = filled($validated['color'] ?? null) ? trim((string) $validated['color']) : null;
        $validated['link'] = filled($validated['link'] ?? null) ? trim((string) $validated['link']) : null;
        $validated['description'] = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;
        $validated['popup_video_url'] = filled($validated['popup_video_url'] ?? null)
            ? trim((string) $validated['popup_video_url'])
            : null;

        // Action-specific cleanup (keep DB tidy)
        $action = $validated['action_type'] ?? null;
        if ($action === CatalogPdfHotspot::ACTION_INTERNAL_PAGE) {
            $validated['link'] = null;
            $validated['description'] = null;
            $validated['price'] = null;
            $validated['popup_video_url'] = null;
        }

        if ($action === CatalogPdfHotspot::ACTION_EXTERNAL_LINK) {
            $validated['internal_page_number'] = null;
            $validated['description'] = null;
            $validated['price'] = null;
            $validated['popup_video_url'] = null;
        }

        if ($action === CatalogPdfHotspot::ACTION_POPUP_WINDOW) {
            $validated['internal_page_number'] = null;
            $validated['popup_video_url'] = null;
        }

        if ($action === CatalogPdfHotspot::ACTION_POPUP_IMAGE) {
            $validated['link'] = null;
            $validated['internal_page_number'] = null;
            $validated['description'] = null;
            $validated['price'] = null;
            $validated['popup_video_url'] = null;
        }

        if ($action === CatalogPdfHotspot::ACTION_POPUP_VIDEO) {
            $validated['link'] = null;
            $validated['internal_page_number'] = null;
            $validated['description'] = null;
            $validated['price'] = null;
        }

        return $validated;
    }

    private function handleHotspotUploads(Request $request, CatalogPdfHotspot $hotspot, string $disk): void
    {
        $baseDir = 'catalog-slicer/' . $hotspot->catalog_pdf_id . '/hotspots/' . $hotspot->id;

        if ($request->hasFile('thumbnail')) {
            $this->deleteFileIfExists($hotspot->thumbnail_disk, $hotspot->thumbnail_path);
            $path = $request->file('thumbnail')->store($baseDir, $disk);
            $hotspot->thumbnail_disk = $disk;
            $hotspot->thumbnail_path = $path;
        }

        if ($request->hasFile('popup_image')) {
            $this->deleteFileIfExists($hotspot->popup_image_disk, $hotspot->popup_image_path);
            $path = $request->file('popup_image')->store($baseDir, $disk);
            $hotspot->popup_image_disk = $disk;
            $hotspot->popup_image_path = $path;
        }

        if ($request->hasFile('popup_video')) {
            $this->deleteFileIfExists($hotspot->popup_video_disk, $hotspot->popup_video_path);
            $path = $request->file('popup_video')->store($baseDir, $disk);
            $hotspot->popup_video_disk = $disk;
            $hotspot->popup_video_path = $path;
            $hotspot->popup_video_url = null;
        }

        if ($request->has('popup_video_url')) {
            $popupVideoUrl = $request->filled('popup_video_url')
                ? trim((string) $request->input('popup_video_url'))
                : null;

            $hotspot->popup_video_url = $popupVideoUrl;

            if ($popupVideoUrl !== null) {
                $this->deleteFileIfExists($hotspot->popup_video_disk, $hotspot->popup_video_path);
                $hotspot->popup_video_disk = null;
                $hotspot->popup_video_path = null;
            }
        }

        $hotspot->save();
    }

    private function syncHotspotMediaForAction(CatalogPdfHotspot $hotspot): void
    {
        $action = $hotspot->action_type;
        $usesThumbnail = $action === CatalogPdfHotspot::ACTION_POPUP_WINDOW;

        if (!$usesThumbnail) {
            $this->deleteFileIfExists($hotspot->thumbnail_disk, $hotspot->thumbnail_path);
            $hotspot->thumbnail_disk = null;
            $hotspot->thumbnail_path = null;
        }

        if ($action !== CatalogPdfHotspot::ACTION_POPUP_IMAGE) {
            $this->deleteFileIfExists($hotspot->popup_image_disk, $hotspot->popup_image_path);
            $hotspot->popup_image_disk = null;
            $hotspot->popup_image_path = null;
        }

        if ($action !== CatalogPdfHotspot::ACTION_POPUP_VIDEO) {
            $this->deleteFileIfExists($hotspot->popup_video_disk, $hotspot->popup_video_path);
            $hotspot->popup_video_disk = null;
            $hotspot->popup_video_path = null;
            $hotspot->popup_video_url = null;
        }
    }

    private function deleteHotspotMedia(CatalogPdfHotspot $hotspot): void
    {
        $this->deleteFileIfExists($hotspot->thumbnail_disk, $hotspot->thumbnail_path);
        $this->deleteFileIfExists($hotspot->popup_image_disk, $hotspot->popup_image_path);
        $this->deleteFileIfExists($hotspot->popup_video_disk, $hotspot->popup_video_path);
    }

    private function deleteFileIfExists(?string $disk, ?string $path): void
    {
        if (!$disk || !$path) {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function assertSlicer(CatalogPdf $pdf): void
    {
        abort_unless($pdf->supportsSlicer(), 404);
    }

    private function authorizePdfAccess(CatalogPdf $pdf): void
    {
        if ($this->currentUserIsAdmin()) {
            return;
        }

        if ($pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function currentUserIsAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    private function authorizeViewer(CatalogPdf $pdf): void
    {
        if ($pdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function serializeHotspot(CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot): array
    {
        return [
            'id' => (int) $hotspot->id,
            'catalog_pdf_id' => (int) $hotspot->catalog_pdf_id,
            'catalog_pdf_page_id' => (int) $hotspot->catalog_pdf_page_id,
            'display_order' => (int) $hotspot->display_order,
            'shape_type' => (string) $hotspot->shape_type,
            'shape_data' => $hotspot->shape_data,
            'runtime_shape' => $this->runtimeShapeForHotspot($hotspot),
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

    private function runtimeShapeForHotspot(CatalogPdfHotspot $hotspot): array
    {
        $shapeData = is_array($hotspot->shape_data) ? $hotspot->shape_data : [];
        $runtimeShape = $shapeData['runtimeShape'] ?? $shapeData['runtime_shape'] ?? null;

        if (is_array($runtimeShape)) {
            return $this->normalizeRuntimeShape($runtimeShape, $hotspot);
        }

        return [
            'type' => $hotspot->shape_type === CatalogPdfHotspot::SHAPE_RECTANGLE ? 'rectangle' : 'polygon',
            'points' => [
                ['x' => (float) $hotspot->x, 'y' => (float) $hotspot->y],
                ['x' => (float) ($hotspot->x + $hotspot->w), 'y' => (float) $hotspot->y],
                ['x' => (float) ($hotspot->x + $hotspot->w), 'y' => (float) ($hotspot->y + $hotspot->h)],
                ['x' => (float) $hotspot->x, 'y' => (float) ($hotspot->y + $hotspot->h)],
            ],
            'bbox' => [
                'x' => (float) $hotspot->x,
                'y' => (float) $hotspot->y,
                'w' => (float) $hotspot->w,
                'h' => (float) $hotspot->h,
            ],
        ];
    }

    private function normalizeRuntimeShape(array $runtimeShape, CatalogPdfHotspot $hotspot): array
    {
        $points = collect($runtimeShape['points'] ?? [])
            ->filter(fn($point) => is_array($point) && isset($point['x'], $point['y']))
            ->map(function (array $point) {
                return [
                    'x' => max(0, min(1, (float) $point['x'])),
                    'y' => max(0, min(1, (float) $point['y'])),
                ];
            })
            ->values()
            ->all();

        if (count($points) < 3) {
            $points = [
                ['x' => (float) $hotspot->x, 'y' => (float) $hotspot->y],
                ['x' => (float) ($hotspot->x + $hotspot->w), 'y' => (float) $hotspot->y],
                ['x' => (float) ($hotspot->x + $hotspot->w), 'y' => (float) ($hotspot->y + $hotspot->h)],
                ['x' => (float) $hotspot->x, 'y' => (float) ($hotspot->y + $hotspot->h)],
            ];
        }

        return [
            'type' => in_array($runtimeShape['type'] ?? null, ['rectangle', 'polygon', 'free'], true)
                ? (string) $runtimeShape['type']
                : ($hotspot->shape_type === CatalogPdfHotspot::SHAPE_RECTANGLE ? 'rectangle' : 'polygon'),
            'points' => $points,
            'bbox' => [
                'x' => (float) $hotspot->x,
                'y' => (float) $hotspot->y,
                'w' => (float) $hotspot->w,
                'h' => (float) $hotspot->h,
            ],
        ];
    }
}
