<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfSharePreviewSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogPdfSharePreviewController extends Controller
{
    public function index()
    {
        $baseQuery = CatalogPdf::query()
            ->with(['user', 'sharePreviewSetting'])
            ->when(!$this->currentUserIsAdmin(), fn(Builder $query) => $query->where('user_id', Auth::id()));

        $pdfs = (clone $baseQuery)
            ->latest()
            ->paginate(12);

        return view('pages.apps.catalog.share-preview-index', [
            'pdfs' => $pdfs,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'public' => (clone $baseQuery)->where('visibility', CatalogPdf::VISIBILITY_PUBLIC)->count(),
                'configured' => (clone $baseQuery)->whereHas('sharePreviewSetting')->count(),
                'video_backgrounds' => (clone $baseQuery)
                    ->whereHas('sharePreviewSetting', fn(Builder $query) => $query->where('background_type', CatalogPdfSharePreviewSetting::BACKGROUND_VIDEO))
                    ->count(),
            ],
            'templateTypes' => CatalogPdf::templateTypeOptions(),
        ]);
    }

    public function edit(CatalogPdf $catalogPdf)
    {
        $this->authorizeManagementAccess($catalogPdf);

        $setting = $this->resolveSetting($catalogPdf);

        return view('pages.apps.catalog.share-preview-editor', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'backgroundTypeOptions' => CatalogPdfSharePreviewSetting::backgroundTypeOptions(),
            'shareAppearance' => $this->shareAppearancePayload($catalogPdf, $setting),
            'shareUrl' => route('catalog.pdfs.share', $catalogPdf),
        ]);
    }

    public function update(Request $request, CatalogPdf $catalogPdf)
    {
        $this->authorizeManagementAccess($catalogPdf);

        $setting = $this->resolveSetting($catalogPdf);

        $validated = $request->validate([
            'background_type' => ['required', 'string', 'in:' . implode(',', array_keys(CatalogPdfSharePreviewSetting::backgroundTypeOptions()))],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'toolbar_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'toolbar_is_visible' => ['nullable', 'boolean'],
            'background_image' => ['nullable', 'image', 'max:10240'],
            'background_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:51200'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'logo_title' => ['nullable', 'string', 'max:120'],
            'logo_position_x' => ['required', 'integer', 'min:0', 'max:100'],
            'logo_position_y' => ['required', 'integer', 'min:0', 'max:100'],
            'logo_width' => ['required', 'integer', 'min:60', 'max:320'],
            'remove_background_image' => ['nullable', 'boolean'],
            'remove_background_video' => ['nullable', 'boolean'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $willHaveImage = $request->hasFile('background_image')
            || (!$request->boolean('remove_background_image') && $setting->hasBackgroundImage());
        $willHaveVideo = $request->hasFile('background_video')
            || (!$request->boolean('remove_background_video') && $setting->hasBackgroundVideo());

        if ($validated['background_type'] === CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE && !$willHaveImage) {
            return redirect()
                ->back()
                ->withErrors(['background_image' => 'Upload a background image before using the image background mode.'])
                ->withInput();
        }

        if ($validated['background_type'] === CatalogPdfSharePreviewSetting::BACKGROUND_VIDEO && !$willHaveVideo) {
            return redirect()
                ->back()
                ->withErrors(['background_video' => 'Upload a background video before using the video background mode.'])
                ->withInput();
        }

        if ($request->boolean('remove_background_image')) {
            $this->clearAsset($setting, 'background_image');
        }

        if ($request->boolean('remove_background_video')) {
            $this->clearAsset($setting, 'background_video');
        }

        if ($request->boolean('remove_logo')) {
            $this->clearAsset($setting, 'logo');
        }

        if ($request->hasFile('background_image')) {
            $this->replaceAsset($catalogPdf, $setting, 'background_image', $request->file('background_image'));
        }

        if ($request->hasFile('background_video')) {
            $this->replaceAsset($catalogPdf, $setting, 'background_video', $request->file('background_video'));
        }

        if ($request->hasFile('logo')) {
            $this->replaceAsset($catalogPdf, $setting, 'logo', $request->file('logo'));
        }

        $setting->fill([
            'background_type' => $validated['background_type'],
            'background_color' => strtoupper($validated['background_color']),
            'toolbar_background_color' => strtoupper($validated['toolbar_background_color']),
            'toolbar_is_visible' => $request->boolean('toolbar_is_visible'),
            'logo_title' => $validated['logo_title'] ?? null,
            'logo_position_x' => (int) $validated['logo_position_x'],
            'logo_position_y' => (int) $validated['logo_position_y'],
            'logo_width' => (int) $validated['logo_width'],
        ]);
        $setting->save();

        return redirect()
            ->route('catalog.pdfs.share-preview.edit', $catalogPdf)
            ->with('success', 'Share preview settings saved.');
    }

    public function asset(CatalogPdf $catalogPdf, string $asset): BinaryFileResponse
    {
        abort_unless(in_array($asset, ['background-image', 'background-video', 'logo'], true), 404);

        $this->authorizeViewerAccess($catalogPdf);

        $setting = $catalogPdf->sharePreviewSetting;
        abort_unless($setting, 404);

        $assetConfig = $setting->assetFor($asset);
        abort_unless($assetConfig, 404);
        abort_unless(Storage::disk($assetConfig['disk'])->exists($assetConfig['path']), 404);

        return response()->file(
            Storage::disk($assetConfig['disk'])->path($assetConfig['path']),
            [
                'Content-Type' => $assetConfig['mime'] ?: 'application/octet-stream',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    private function resolveSetting(CatalogPdf $catalogPdf): CatalogPdfSharePreviewSetting
    {
        $setting = $catalogPdf->sharePreviewSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        if (!filled($setting->background_type)) {
            $setting->applyDefaults();
            $setting->save();
        }

        return $setting;
    }

    private function shareAppearancePayload(CatalogPdf $catalogPdf, CatalogPdfSharePreviewSetting $setting): array
    {
        $appearance = $setting->appearanceSettings();

        $appearance['backgroundImageUrl'] = $setting->hasBackgroundImage()
            ? route('catalog.pdfs.share-preview.asset', [$catalogPdf, 'background-image'])
            : null;
        $appearance['backgroundVideoUrl'] = $setting->hasBackgroundVideo()
            ? route('catalog.pdfs.share-preview.asset', [$catalogPdf, 'background-video'])
            : null;
        $appearance['logoUrl'] = $setting->hasLogo()
            ? route('catalog.pdfs.share-preview.asset', [$catalogPdf, 'logo'])
            : null;
        $appearance['hasBranding'] = filled($appearance['logoTitle']) || filled($appearance['logoUrl']);

        return $appearance;
    }

    private function replaceAsset(CatalogPdf $catalogPdf, CatalogPdfSharePreviewSetting $setting, string $prefix, UploadedFile $file): void
    {
        $this->clearAsset($setting, $prefix);

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $path = $file->storeAs(
            'catalog-share-preview/' . $catalogPdf->id,
            $prefix . '-' . Str::uuid() . '.' . $extension,
            'local'
        );

        $setting->{$prefix . '_disk'} = 'local';
        $setting->{$prefix . '_path'} = $path;
        $setting->{$prefix . '_mime'} = $file->getClientMimeType();
    }

    private function clearAsset(CatalogPdfSharePreviewSetting $setting, string $prefix): void
    {
        $disk = $setting->{$prefix . '_disk'} ?: 'local';
        $path = $setting->{$prefix . '_path'};

        if (filled($path) && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        $setting->{$prefix . '_disk'} = null;
        $setting->{$prefix . '_path'} = null;
        $setting->{$prefix . '_mime'} = null;
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
}
