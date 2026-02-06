<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\CatalogPdfHotspot;
use App\Models\CatalogPdfPage;
use App\Services\CatalogPdfSlicerImageGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
            'shapeOptions' => CatalogPdfHotspot::shapeOptions(),
            'actionOptions' => CatalogPdfHotspot::actionOptions(),
        ]);
    }

    public function preview(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get();

        return view('pages.apps.catalog.slicer-preview', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
        ]);
    }

    public function live(CatalogPdf $catalogPdf)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $pages = $catalogPdf->pages()
            ->orderBy('display_order')
            ->get(['id', 'page_number', 'display_order', 'title', 'image_disk', 'image_path', 'image_width', 'image_height']);

        $hotspots = $catalogPdf->hotspots()
            ->where('is_active', true)
            ->get();

        return view('pages.apps.catalog.slicer-live', [
            'pdf' => $catalogPdf,
            'pages' => $pages,
            'hotspots' => $hotspots,
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
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
        $this->authorizePdfAccess($catalogPdf);
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
            ->get();

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
            'data' => $hotspot,
        ], 201);
    }

    public function updateHotspot(Request $request, CatalogPdf $catalogPdf, CatalogPdfHotspot $hotspot)
    {
        $this->assertSlicer($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);
        abort_unless($hotspot->catalog_pdf_id === $catalogPdf->id, 404);

        $validated = $this->validateHotspot($request, isUpdate: true);
        $disk = $catalogPdf->storage_disk;

        $hotspot = DB::transaction(function () use ($validated, $request, $hotspot, $disk) {
            $hotspot->fill($validated);
            $hotspot->save();

            $this->handleHotspotUploads($request, $hotspot, $disk);

            return $hotspot->fresh();
        });

        return response()->json([
            'data' => $hotspot,
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
        $this->authorizePdfAccess($catalogPdf);
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

    public function track(Request $request, CatalogPdf $catalogPdf)
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

        CatalogPdfEvent::create([
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

        return response()->json(['ok' => true]);
    }

    private function validateHotspot(Request $request, bool $isUpdate = false): array
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

        // Normalize booleans
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        // Action-specific cleanup (keep DB tidy)
        $action = $validated['action_type'] ?? null;
        if ($action === CatalogPdfHotspot::ACTION_INTERNAL_PAGE) {
            $validated['popup_video_url'] = null;
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
        }

        if ($request->filled('popup_video_url')) {
            $hotspot->popup_video_url = $request->input('popup_video_url');
        }

        $hotspot->save();
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
        abort_unless($pdf->isSlicerTemplate(), 404);
    }

    private function authorizePdfAccess(CatalogPdf $pdf): void
    {
        if ($pdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
