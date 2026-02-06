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

        $setting = $catalogPdf->flipPhysicsSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        if (!$setting->preset) {
            $setting->applyPreset(CatalogPdfFlipPhysicsSetting::PRESET_REALISTIC);
            $setting->save();
        }

        return view('pages.apps.catalog.flip-physics', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'presetOptions' => CatalogPdfFlipPhysicsSetting::presetOptions(),
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
        ]);
    }

    public function preview(CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);
        $this->authorizePdfAccess($catalogPdf);

        $setting = $catalogPdf->flipPhysicsSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        if (!$setting->preset) {
            $setting->applyPreset(CatalogPdfFlipPhysicsSetting::PRESET_REALISTIC);
            $setting->save();
        }

        return view('pages.apps.catalog.flip-physics-preview', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
        ]);
    }

    public function share(CatalogPdf $catalogPdf)
    {
        $this->assertFlipPhysics($catalogPdf);

        // For private PDFs, only owner can access
        if ($catalogPdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $catalogPdf->user_id !== Auth::id()) {
            abort(403);
        }

        $setting = $catalogPdf->flipPhysicsSetting()->firstOrCreate([
            'catalog_pdf_id' => $catalogPdf->id,
        ]);

        if (!$setting->preset) {
            $setting->applyPreset(CatalogPdfFlipPhysicsSetting::PRESET_REALISTIC);
            $setting->save();
        }

        return view('pages.apps.catalog.flip-physics-share', [
            'pdf' => $catalogPdf,
            'setting' => $setting,
            'pdfUrl' => route('catalog.pdfs.source', $catalogPdf),
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

        // Apply preset first, then override with user inputs
        $setting->applyPreset($validated['preset']);

        $setting->duration_ms = (int) $validated['duration_ms'];
        $setting->gradients = (bool) ($validated['gradients'] ?? false);
        $setting->acceleration = (bool) ($validated['acceleration'] ?? false);
        $setting->elevation = (int) $validated['elevation'];
        $setting->display_mode = $validated['display_mode'];
        $setting->render_scale_percent = (int) $validated['render_scale_percent'];
        $setting->save();

        return redirect()
            ->route('catalog.pdfs.flip-physics.edit', $catalogPdf)
            ->with('success', 'Flip Physics settings saved.');
    }

    private function assertFlipPhysics(CatalogPdf $pdf): void
    {
        abort_unless($pdf->template_type === CatalogPdf::TEMPLATE_FLIP_PHYSICS, 404);
    }

    private function authorizePdfAccess(CatalogPdf $pdf): void
    {
        if ($pdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $pdf->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
