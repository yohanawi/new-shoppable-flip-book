<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfSharePreviewSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogPdfSharePreviewStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_preview_index_lists_customer_pdfs(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $other */
        $other = User::factory()->create();

        CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Owner Preview Catalog',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/owner-preview-catalog.pdf',
            'original_filename' => 'owner-preview-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        CatalogPdf::create([
            'user_id' => $other->id,
            'title' => 'Other Preview Catalog',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/other-preview-catalog.pdf',
            'original_filename' => 'other-preview-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        $response = $this->actingAs($owner)->get(route('catalog.pdfs.share-preview.index'));

        $response->assertOk();
        $response->assertSee('Owner Preview Catalog');
        $response->assertDontSee('Other Preview Catalog');
        $response->assertSee('Share Preview Studio');
    }

    public function test_customer_can_save_share_preview_and_public_share_uses_it(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Styled Share Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/styled-share-catalog.pdf',
            'original_filename' => 'styled-share-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $response = $this->actingAs($user)->post(route('catalog.pdfs.share-preview.update', $pdf), [
            'background_type' => CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE,
            'background_color' => '#102A43',
            'toolbar_background_color' => '#1E3A8A',
            'toolbar_is_visible' => '1',
            'background_image' => UploadedFile::fake()->image('share-background.jpg', 1600, 900),
            'logo' => UploadedFile::fake()->image('brand-logo.png', 320, 120),
            'logo_title' => 'Spring 2026 Collection',
            'logo_position_x' => 22,
            'logo_position_y' => 14,
            'logo_width' => 196,
        ]);

        $response->assertRedirect(route('catalog.pdfs.share-preview.edit', $pdf));
        $response->assertSessionHas('success', 'Share preview settings saved.');

        $setting = $pdf->sharePreviewSetting()->first();

        $this->assertNotNull($setting);
        $this->assertTrue(Storage::disk('local')->exists($setting->background_image_path));
        $this->assertTrue(Storage::disk('local')->exists($setting->logo_path));

        $this->assertDatabaseHas('catalog_pdf_share_preview_settings', [
            'catalog_pdf_id' => $pdf->id,
            'background_type' => CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE,
            'background_color' => '#102A43',
            'toolbar_background_color' => '#1E3A8A',
            'toolbar_is_visible' => true,
            'logo_title' => 'Spring 2026 Collection',
            'logo_position_x' => 22,
            'logo_position_y' => 14,
            'logo_width' => 196,
        ]);

        $shareResponse = $this->get(route('catalog.pdfs.share', $pdf));
        $shareResponse->assertOk();
        $shareResponse->assertSee('Spring 2026 Collection');
        $shareResponse->assertSee(route('catalog.pdfs.share-preview.asset', [$pdf, 'background-image']), false);
        $shareResponse->assertSee(route('catalog.pdfs.share-preview.asset', [$pdf, 'logo']), false);
        $shareResponse->assertSee('Branded preview');
        $shareResponse->assertSee('--share-toolbar-bg: #1E3A8A;', false);
        $shareResponse->assertSee('<div class="share-toolbar">', false);

        $this->get(route('catalog.pdfs.share-preview.asset', [$pdf, 'background-image']))->assertOk();
        $this->get(route('catalog.pdfs.share-preview.asset', [$pdf, 'logo']))->assertOk();
    }

    public function test_customer_can_hide_toolbar_on_public_share_preview(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Hidden Toolbar Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/hidden-toolbar-catalog.pdf',
            'original_filename' => 'hidden-toolbar-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $this->actingAs($user)->post(route('catalog.pdfs.share-preview.update', $pdf), [
            'background_type' => CatalogPdfSharePreviewSetting::BACKGROUND_COLOR,
            'background_color' => '#0F172A',
            'toolbar_background_color' => '#111827',
            'toolbar_is_visible' => '0',
            'logo_position_x' => 8,
            'logo_position_y' => 8,
            'logo_width' => 168,
        ])->assertRedirect(route('catalog.pdfs.share-preview.edit', $pdf));

        $this->assertDatabaseHas('catalog_pdf_share_preview_settings', [
            'catalog_pdf_id' => $pdf->id,
            'toolbar_background_color' => '#111827',
            'toolbar_is_visible' => false,
        ]);

        $shareResponse = $this->get(route('catalog.pdfs.share', $pdf));
        $shareResponse->assertOk();
        $shareResponse->assertSee('--share-toolbar-bg: #111827;', false);
        $shareResponse->assertDontSee('<div class="share-toolbar">', false);
    }
}
