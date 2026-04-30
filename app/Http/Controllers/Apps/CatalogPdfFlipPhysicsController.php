<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfFlipPhysicsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogPdfFlipPhysicsController extends Controller
{
    public function edit(CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $setting = $this->resolveSetting($catalogPdf);

        return view('pages.apps.catalog.flip-physics', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'presetOptions' => CatalogPdfFlipPhysicsSetting::presetOptions(),
            'presetDefaults' => CatalogPdfFlipPhysicsSetting::presetDefaults(),
            'viewerSettings' => $setting->viewerSettings(),
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function preview(CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $setting = $this->resolveSetting($catalogPdf);

        return view('pages.apps.catalog.flip-physics-preview', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'viewerSettings' => $setting->viewerSettings(),
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function share(CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);

        if (
            !Auth::user()?->isAdmin()
            && $catalogPdf->visibility === CatalogPdf::VISIBILITY_PRIVATE
            && $catalogPdf->user_id !== Auth::id()
        ) {
            abort(403);
        }

        $setting = $this->resolveSetting($catalogPdf);

        return view('pages.apps.catalog.flip-physics-share', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'viewerSettings' => $setting->viewerSettings(),
            'pdfUrl' => route('catalog.pdfs.file', $catalogPdf),
        ]);
    }

    public function update(Request $request, CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $setting = $catalogPdf->flipPhysicsSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        $presetKeys = array_keys(CatalogPdfFlipPhysicsSetting::presetOptions());

        $validated = $request->validate([
            'preset' => ['required', 'string', 'in:' . implode(',', $presetKeys)],
            'duration_ms' => ['required', 'integer', 'min:200', 'max:4000'],
            'gradients' => ['nullable', 'boolean'],
            'acceleration' => ['nullable', 'boolean'],
            'elevation' => ['required', 'integer', 'min:0', 'max:100'],
            'display_mode' => ['required', 'string', 'in:auto,single,double'],
            'render_scale_percent' => ['required', 'integer', 'min:80', 'max:200'],
        ]);

        $setting->applyPreset($validated['preset'], [
            'duration_ms' => (int) $validated['duration_ms'],
            'gradients' => $request->boolean('gradients'),
            'acceleration' => $request->boolean('acceleration'),
            'elevation' => (int) $validated['elevation'],
            'display_mode' => $validated['display_mode'],
            'render_scale_percent' => (int) $validated['render_scale_percent'],
        ]);
        $setting->save();

        return redirect()
            ->route('catalog.pdfs.flip-physics.edit', $catalogPdf)
            ->with('success', 'Flip Physics settings saved.');
    }

    private function assertFlipPhysics(CatalogPdf $pdf): void
    {
        abort_unless($pdf->supportsFlipPhysics(), 404);
    }

    private function resolveSetting(CatalogPdf $catalogPdf): CatalogPdfFlipPhysicsSetting
    {
        $setting = $catalogPdf->flipPhysicsSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        if (!$setting->preset) {
            $setting->applyPreset(CatalogPdfFlipPhysicsSetting::defaultPreset());
            $setting->save();
        }

        return $setting;
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
}
