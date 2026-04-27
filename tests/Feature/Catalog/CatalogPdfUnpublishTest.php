<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfHotspot;
use App\Models\CatalogPdfPage;
use App\Models\CatalogPdfSharePreviewSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogPdfUnpublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_moves_private_catalog_assets_to_public_storage_and_restores_guest_access(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        /** @var User $owner */
        $owner = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Private Slicer Catalog',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/private-slicer.pdf',
            'original_filename' => 'private-slicer.pdf',
            'mime_type' => 'application/pdf',
            'size' => 256,
        ]);

        $page = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 1,
            'display_order' => 1,
            'title' => 'Page 1',
            'is_locked' => false,
            'is_hidden' => false,
            'image_disk' => 'local',
            'image_path' => 'catalog-slicer/' . $pdf->id . '/pages/page-0001.jpg',
        ]);

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => ['type' => 'rectangle'],
            'x' => 0.1,
            'y' => 0.2,
            'w' => 0.3,
            'h' => 0.2,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_WINDOW,
            'is_active' => true,
            'title' => 'Shop now',
            'thumbnail_disk' => 'local',
            'thumbnail_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/thumb.png',
            'popup_image_disk' => 'local',
            'popup_image_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/popup-image.png',
            'popup_video_disk' => 'local',
            'popup_video_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/popup-video.mp4',
            'description' => 'Window content',
            'price' => 10,
        ]);

        $setting = CatalogPdfSharePreviewSetting::create([
            'catalog_pdf_id' => $pdf->id,
            'background_type' => CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE,
            'background_color' => '#0F172A',
            'toolbar_background_color' => '#020617',
            'toolbar_is_visible' => true,
            'background_image_disk' => 'local',
            'background_image_path' => 'catalog-share-preview/' . $pdf->id . '/background.png',
            'background_image_mime' => 'image/png',
            'background_video_disk' => 'local',
            'background_video_path' => 'catalog-share-preview/' . $pdf->id . '/background.mp4',
            'background_video_mime' => 'video/mp4',
            'logo_disk' => 'local',
            'logo_path' => 'catalog-share-preview/' . $pdf->id . '/logo.png',
            'logo_mime' => 'image/png',
            'logo_title' => 'Brand',
            'logo_position_x' => 8,
            'logo_position_y' => 8,
            'logo_width' => 168,
        ]);

        foreach (
            [
                $pdf->pdf_path => 'pdf-bytes',
                $page->image_path => 'image-bytes',
                $hotspot->thumbnail_path => 'thumb-bytes',
                $hotspot->popup_image_path => 'popup-image-bytes',
                $hotspot->popup_video_path => 'popup-video-bytes',
                $setting->background_image_path => 'background-image-bytes',
                $setting->background_video_path => 'background-video-bytes',
                $setting->logo_path => 'logo-bytes',
            ] as $path => $contents
        ) {
            Storage::disk('local')->put($path, $contents);
        }

        $response = $this->actingAs($owner)->patch(route('catalog.pdfs.publish', $pdf));

        $response->assertRedirect(route('catalog.pdfs.index'));
        $response->assertSessionHas('success', 'PDF published successfully. Public share access is enabled.');

        $pdf->refresh();
        $page->refresh();
        $hotspot->refresh();
        $setting->refresh();

        $this->assertSame(CatalogPdf::VISIBILITY_PUBLIC, $pdf->visibility);
        $this->assertSame('public', $pdf->storage_disk);
        $this->assertSame('public', $page->image_disk);
        $this->assertSame('public', $hotspot->thumbnail_disk);
        $this->assertSame('public', $hotspot->popup_image_disk);
        $this->assertSame('public', $hotspot->popup_video_disk);
        $this->assertSame('public', $setting->background_image_disk);
        $this->assertSame('public', $setting->background_video_disk);
        $this->assertSame('public', $setting->logo_disk);

        foreach (
            [
                $pdf->pdf_path,
                $page->image_path,
                $hotspot->thumbnail_path,
                $hotspot->popup_image_path,
                $hotspot->popup_video_path,
                $setting->background_image_path,
                $setting->background_video_path,
                $setting->logo_path,
            ] as $path
        ) {
            $this->assertTrue(Storage::disk('public')->exists($path));
            $this->assertFalse(Storage::disk('local')->exists($path));
        }

        auth()->guard()->logout();

        $this->get(route('catalog.pdfs.share', $pdf))->assertOk();
        $this->get(route('catalog.pdfs.file', $pdf))->assertOk();
        $this->get(route('catalog.pdfs.slicer.pages.image', [$pdf, $page]))->assertOk();
        $this->get(route('catalog.pdfs.slicer.hotspots.media', [$pdf, $hotspot, 'thumbnail']))->assertOk();
        $this->get(route('catalog.pdfs.share-preview.asset', [$pdf, 'background-image']))->assertOk();
    }

    public function test_unpublish_moves_public_catalog_assets_to_private_storage_and_blocks_guest_access(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        /** @var User $owner */
        $owner = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Public Slicer Catalog',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/public-slicer.pdf',
            'original_filename' => 'public-slicer.pdf',
            'mime_type' => 'application/pdf',
            'size' => 256,
        ]);

        $page = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 1,
            'display_order' => 1,
            'title' => 'Page 1',
            'is_locked' => false,
            'is_hidden' => false,
            'image_disk' => 'public',
            'image_path' => 'catalog-slicer/' . $pdf->id . '/pages/page-0001.jpg',
        ]);

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => ['type' => 'rectangle'],
            'x' => 0.1,
            'y' => 0.2,
            'w' => 0.3,
            'h' => 0.2,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_WINDOW,
            'is_active' => true,
            'title' => 'Shop now',
            'thumbnail_disk' => 'public',
            'thumbnail_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/thumb.png',
            'popup_image_disk' => 'public',
            'popup_image_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/popup-image.png',
            'popup_video_disk' => 'public',
            'popup_video_path' => 'catalog-slicer/' . $pdf->id . '/hotspots/' . $page->id . '/popup-video.mp4',
            'description' => 'Window content',
            'price' => 10,
        ]);

        $setting = CatalogPdfSharePreviewSetting::create([
            'catalog_pdf_id' => $pdf->id,
            'background_type' => CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE,
            'background_color' => '#0F172A',
            'toolbar_background_color' => '#020617',
            'toolbar_is_visible' => true,
            'background_image_disk' => 'public',
            'background_image_path' => 'catalog-share-preview/' . $pdf->id . '/background.png',
            'background_image_mime' => 'image/png',
            'background_video_disk' => 'public',
            'background_video_path' => 'catalog-share-preview/' . $pdf->id . '/background.mp4',
            'background_video_mime' => 'video/mp4',
            'logo_disk' => 'public',
            'logo_path' => 'catalog-share-preview/' . $pdf->id . '/logo.png',
            'logo_mime' => 'image/png',
            'logo_title' => 'Brand',
            'logo_position_x' => 8,
            'logo_position_y' => 8,
            'logo_width' => 168,
        ]);

        foreach (
            [
                $pdf->pdf_path => 'pdf-bytes',
                $page->image_path => 'image-bytes',
                $hotspot->thumbnail_path => 'thumb-bytes',
                $hotspot->popup_image_path => 'popup-image-bytes',
                $hotspot->popup_video_path => 'popup-video-bytes',
                $setting->background_image_path => 'background-image-bytes',
                $setting->background_video_path => 'background-video-bytes',
                $setting->logo_path => 'logo-bytes',
            ] as $path => $contents
        ) {
            Storage::disk('public')->put($path, $contents);
        }

        $response = $this->actingAs($owner)->patch(route('catalog.pdfs.unpublish', $pdf));

        $response->assertRedirect(route('catalog.pdfs.index'));
        $response->assertSessionHas('success', 'PDF unpublished successfully. It is now private.');

        $pdf->refresh();
        $page->refresh();
        $hotspot->refresh();
        $setting->refresh();

        $this->assertSame(CatalogPdf::VISIBILITY_PRIVATE, $pdf->visibility);
        $this->assertSame('local', $pdf->storage_disk);
        $this->assertSame('local', $page->image_disk);
        $this->assertSame('local', $hotspot->thumbnail_disk);
        $this->assertSame('local', $hotspot->popup_image_disk);
        $this->assertSame('local', $hotspot->popup_video_disk);
        $this->assertSame('local', $setting->background_image_disk);
        $this->assertSame('local', $setting->background_video_disk);
        $this->assertSame('local', $setting->logo_disk);

        foreach (
            [
                $pdf->pdf_path,
                $page->image_path,
                $hotspot->thumbnail_path,
                $hotspot->popup_image_path,
                $hotspot->popup_video_path,
                $setting->background_image_path,
                $setting->background_video_path,
                $setting->logo_path,
            ] as $path
        ) {
            $this->assertFalse(Storage::disk('public')->exists($path));
            $this->assertTrue(Storage::disk('local')->exists($path));
        }

        auth()->guard()->logout();

        $this->get(route('catalog.pdfs.share', $pdf))->assertForbidden();
        $this->get(route('catalog.pdfs.file', $pdf))->assertForbidden();
        $this->get(route('catalog.pdfs.slicer.pages.image', [$pdf, $page]))->assertForbidden();
        $this->get(route('catalog.pdfs.slicer.hotspots.media', [$pdf, $hotspot, 'thumbnail']))->assertForbidden();
        $this->get(route('catalog.pdfs.share-preview.asset', [$pdf, 'background-image']))->assertForbidden();

        $this->actingAs($owner)->get(route('catalog.pdfs.file', $pdf))->assertOk();
        $this->actingAs($owner)->get(route('catalog.pdfs.slicer.pages.image', [$pdf, $page]))->assertOk();
        $this->actingAs($owner)->get(route('catalog.pdfs.slicer.hotspots.media', [$pdf, $hotspot, 'thumbnail']))->assertOk();
        $this->actingAs($owner)->get(route('catalog.pdfs.share-preview.asset', [$pdf, 'background-image']))->assertOk();
    }
}
