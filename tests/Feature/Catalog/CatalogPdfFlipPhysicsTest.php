<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfFlipPhysicsSetting;
use App\Models\CatalogPdfHotspot;
use App\Models\CatalogPdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogPdfFlipPhysicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_flip_physics_pdf_exposes_editor_and_initializes_default_setting(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('catalog.pdfs.store'), [
            'title' => 'Physics Catalog',
            'description' => 'Spring campaign',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'pdf' => UploadedFile::fake()->create('physics-catalog.pdf', 256, 'application/pdf'),
        ]);

        $response->assertRedirect(route('catalog.pdfs.index'));

        $pdf = CatalogPdf::query()->where('title', 'Physics Catalog')->firstOrFail();

        $this->assertTrue(Storage::disk('local')->exists($pdf->pdf_path));

        $showResponse = $this->actingAs($user)->get(route('catalog.pdfs.show', $pdf));
        $showResponse->assertOk();
        $showResponse->assertSee(route('catalog.pdfs.flip-physics.edit', $pdf), false);

        $editResponse = $this->actingAs($user)->get(route('catalog.pdfs.flip-physics.edit', $pdf));
        $editResponse->assertOk();
        $editResponse->assertSee('Realistic (default)');
        $editResponse->assertSee('Snappy (fast)');
        $editResponse->assertSee('Smooth (soft)');
        $editResponse->assertSee('Minimal (lightweight)');

        $this->assertDatabaseHas('catalog_pdf_flip_physics_settings', [
            'catalog_pdf_id' => $pdf->id,
            'preset' => CatalogPdfFlipPhysicsSetting::defaultPreset(),
            'duration_ms' => 900,
            'gradients' => true,
            'acceleration' => true,
            'elevation' => 50,
            'display_mode' => 'auto',
            'render_scale_percent' => 120,
        ]);
    }

    public function test_saving_flip_physics_keeps_selected_preset_and_manual_adjustments(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Adjustment Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/adjustment-catalog.pdf',
            'original_filename' => 'adjustment-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $response = $this->actingAs($user)->post(route('catalog.pdfs.flip-physics.update', $pdf), [
            'preset' => CatalogPdfFlipPhysicsSetting::PRESET_SNAPPY,
            'duration_ms' => 780,
            'elevation' => 42,
            'display_mode' => 'double',
            'render_scale_percent' => 130,
        ]);

        $response->assertRedirect(route('catalog.pdfs.flip-physics.edit', $pdf));
        $response->assertSessionHas('success', 'Flip Physics settings saved.');

        $this->assertDatabaseHas('catalog_pdf_flip_physics_settings', [
            'catalog_pdf_id' => $pdf->id,
            'preset' => CatalogPdfFlipPhysicsSetting::PRESET_SNAPPY,
            'duration_ms' => 780,
            'gradients' => false,
            'acceleration' => false,
            'elevation' => 42,
            'display_mode' => 'double',
            'render_scale_percent' => 130,
        ]);
    }

    public function test_preview_and_share_pages_render_with_viewer_settings_payload(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Preview Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/preview-catalog.pdf',
            'original_filename' => 'preview-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $setting = $pdf->flipPhysicsSetting()->create([
            'preset' => CatalogPdfFlipPhysicsSetting::PRESET_SMOOTH,
            'duration_ms' => 1400,
            'gradients' => true,
            'acceleration' => true,
            'elevation' => 64,
            'display_mode' => 'single',
            'render_scale_percent' => 150,
        ]);

        $previewResponse = $this->actingAs($user)->get(route('catalog.pdfs.flip-physics.preview', $pdf));
        $previewResponse->assertOk();
        $previewResponse->assertSee('const settings =', false);
        $previewResponse->assertSee('"displayMode":"single"', false);
        $previewResponse->assertSee('"renderScale":1.5', false);

        $shareResponse = $this->actingAs($user)->get(route('catalog.pdfs.flip-physics.share', $pdf));
        $shareResponse->assertOk();
        $shareResponse->assertSee('const settings =', false);
        $shareResponse->assertSee('"duration":1400', false);
        $shareResponse->assertSee('"elevation":64', false);
    }

    public function test_public_shared_pdf_is_accessible_to_guests_with_combined_edit_data(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Public Shared Catalog',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/public-shared-catalog.pdf',
            'original_filename' => 'public-shared-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $visiblePage = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 1,
            'display_order' => 1,
            'title' => 'Cover',
            'is_locked' => false,
            'is_hidden' => false,
        ]);

        CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 2,
            'display_order' => 2,
            'title' => 'Hidden Page',
            'is_locked' => false,
            'is_hidden' => true,
        ]);

        CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $visiblePage->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => [
                'shape' => 'rectangle',
            ],
            'x' => 0.1,
            'y' => 0.2,
            'w' => 0.3,
            'h' => 0.2,
            'action_type' => CatalogPdfHotspot::ACTION_EXTERNAL_LINK,
            'is_active' => true,
            'title' => 'Shop Now',
            'link' => 'https://example.com/product',
        ]);

        $pdf->flipPhysicsSetting()->create([
            'preset' => CatalogPdfFlipPhysicsSetting::PRESET_SMOOTH,
            'duration_ms' => 1400,
            'gradients' => true,
            'acceleration' => true,
            'elevation' => 64,
            'display_mode' => 'single',
            'render_scale_percent' => 150,
        ]);

        $shareResponse = $this->get(route('catalog.pdfs.share', $pdf));
        $shareResponse->assertOk();
        $shareResponse->assertSee('const settings =', false);
        $shareResponse->assertSee('"duration":1400', false);
        $shareResponse->assertSee('"action_type":"external_link"', false);
        $shareResponse->assertSee('"page_number":1', false);
        $shareResponse->assertDontSee('"page_number":2', false);

        $fileResponse = $this->get(route('catalog.pdfs.file', $pdf));
        $fileResponse->assertOk();
        $fileResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_private_shared_pdf_is_not_accessible_to_guests(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Private Shared Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/private-shared-catalog.pdf',
            'original_filename' => 'private-shared-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $this->get(route('catalog.pdfs.share', $pdf))->assertForbidden();
        $this->get(route('catalog.pdfs.file', $pdf))->assertForbidden();
    }
}
