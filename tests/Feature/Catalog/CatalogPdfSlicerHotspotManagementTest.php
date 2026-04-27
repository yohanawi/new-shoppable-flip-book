<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfHotspot;
use App\Models\CatalogPdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogPdfSlicerHotspotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_hotspot_details_and_switch_popup_video_from_url_to_file(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();
        [$pdf, $page] = $this->createSlicerPdfForUser($user);

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => $this->shapeData(),
            'x' => 0.15,
            'y' => 0.20,
            'w' => 0.25,
            'h' => 0.10,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_VIDEO,
            'is_active' => true,
            'title' => 'Original Video',
            'color' => '#111111',
            'popup_video_url' => 'https://player.example.com/original',
        ]);

        $response = $this->actingAs($user)->post(
            route('catalog.pdfs.slicer.hotspots.update', [$pdf, $hotspot]),
            array_merge($this->baseShapePayload(), [
                '_method' => 'PATCH',
                'action_type' => CatalogPdfHotspot::ACTION_POPUP_VIDEO,
                'title' => 'Updated Video',
                'color' => '#2244FF',
                'popup_video_url' => '',
                'popup_video' => UploadedFile::fake()->create('demo.mp4', 512, 'video/mp4'),
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated Video');
        $response->assertJsonPath('data.color', '#2244FF');
        $response->assertJsonPath('data.popup_video_url', null);

        $hotspot->refresh();

        $this->assertSame('Updated Video', $hotspot->title);
        $this->assertSame('#2244FF', $hotspot->color);
        $this->assertNull($hotspot->popup_video_url);
        $this->assertNotNull($hotspot->popup_video_path);
        $this->assertTrue(Storage::disk('local')->exists($hotspot->popup_video_path));
    }

    public function test_updating_hotspot_to_popup_image_requires_an_image_when_none_exists(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();
        [$pdf, $page] = $this->createSlicerPdfForUser($user);

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => $this->shapeData(),
            'x' => 0.10,
            'y' => 0.20,
            'w' => 0.30,
            'h' => 0.15,
            'action_type' => CatalogPdfHotspot::ACTION_EXTERNAL_LINK,
            'is_active' => true,
            'title' => 'Link Hotspot',
            'link' => 'https://example.com/product',
        ]);

        $response = $this->actingAs($user)->post(
            route('catalog.pdfs.slicer.hotspots.update', [$pdf, $hotspot]),
            array_merge($this->baseShapePayload(), [
                '_method' => 'PATCH',
                'action_type' => CatalogPdfHotspot::ACTION_POPUP_IMAGE,
                'title' => 'Needs Image',
                'link' => '',
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('popup_image');
    }

    public function test_switching_hotspot_action_clears_stale_popup_image_media(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();
        [$pdf, $page] = $this->createSlicerPdfForUser($user);

        $storedPath = 'catalog-slicer/' . $pdf->id . '/hotspots/existing-image.png';
        Storage::disk('local')->put($storedPath, 'image-bytes');

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => $this->shapeData(),
            'x' => 0.20,
            'y' => 0.25,
            'w' => 0.18,
            'h' => 0.12,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_IMAGE,
            'is_active' => true,
            'title' => 'Image Hotspot',
            'popup_image_disk' => 'local',
            'popup_image_path' => $storedPath,
        ]);

        $response = $this->actingAs($user)->post(
            route('catalog.pdfs.slicer.hotspots.update', [$pdf, $hotspot]),
            array_merge($this->baseShapePayload(), [
                '_method' => 'PATCH',
                'action_type' => CatalogPdfHotspot::ACTION_EXTERNAL_LINK,
                'title' => 'External Hotspot',
                'link' => 'https://example.com/updated',
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonPath('data.action_type', CatalogPdfHotspot::ACTION_EXTERNAL_LINK);
        $response->assertJsonPath('data.popup_image_path', null);

        $hotspot->refresh();

        $this->assertSame(CatalogPdfHotspot::ACTION_EXTERNAL_LINK, $hotspot->action_type);
        $this->assertNull($hotspot->popup_image_disk);
        $this->assertNull($hotspot->popup_image_path);
        $this->assertFalse(Storage::disk('local')->exists($storedPath));
    }

    public function test_switching_hotspot_action_from_popup_window_clears_stale_thumbnail_media(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();
        [$pdf, $page] = $this->createSlicerPdfForUser($user);

        $storedPath = 'catalog-slicer/' . $pdf->id . '/hotspots/existing-thumb.png';
        Storage::disk('local')->put($storedPath, 'thumb-bytes');

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => $this->shapeData(),
            'x' => 0.20,
            'y' => 0.25,
            'w' => 0.18,
            'h' => 0.12,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_WINDOW,
            'is_active' => true,
            'title' => 'Product Hotspot',
            'thumbnail_disk' => 'local',
            'thumbnail_path' => $storedPath,
            'description' => 'Current popup window',
            'price' => 20,
        ]);

        $response = $this->actingAs($user)->post(
            route('catalog.pdfs.slicer.hotspots.update', [$pdf, $hotspot]),
            array_merge($this->baseShapePayload(), [
                '_method' => 'PATCH',
                'action_type' => CatalogPdfHotspot::ACTION_EXTERNAL_LINK,
                'title' => 'External Hotspot',
                'link' => 'https://example.com/updated',
            ]),
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJsonPath('data.action_type', CatalogPdfHotspot::ACTION_EXTERNAL_LINK);
        $response->assertJsonPath('data.thumbnail_path', null);

        $hotspot->refresh();

        $this->assertSame(CatalogPdfHotspot::ACTION_EXTERNAL_LINK, $hotspot->action_type);
        $this->assertNull($hotspot->thumbnail_disk);
        $this->assertNull($hotspot->thumbnail_path);
        $this->assertFalse(Storage::disk('local')->exists($storedPath));
    }

    public function test_owner_can_delete_hotspot_and_its_media(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();
        [$pdf, $page] = $this->createSlicerPdfForUser($user);

        $thumbnailPath = 'catalog-slicer/' . $pdf->id . '/hotspots/delete-thumb.png';
        $popupImagePath = 'catalog-slicer/' . $pdf->id . '/hotspots/delete-image.png';
        $popupVideoPath = 'catalog-slicer/' . $pdf->id . '/hotspots/delete-video.mp4';

        Storage::disk('local')->put($thumbnailPath, 'thumb-bytes');
        Storage::disk('local')->put($popupImagePath, 'image-bytes');
        Storage::disk('local')->put($popupVideoPath, 'video-bytes');

        $hotspot = CatalogPdfHotspot::create([
            'catalog_pdf_id' => $pdf->id,
            'catalog_pdf_page_id' => $page->id,
            'display_order' => 1,
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => $this->shapeData(),
            'x' => 0.20,
            'y' => 0.25,
            'w' => 0.18,
            'h' => 0.12,
            'action_type' => CatalogPdfHotspot::ACTION_POPUP_WINDOW,
            'is_active' => true,
            'title' => 'Delete Me',
            'thumbnail_disk' => 'local',
            'thumbnail_path' => $thumbnailPath,
            'popup_image_disk' => 'local',
            'popup_image_path' => $popupImagePath,
            'popup_video_disk' => 'local',
            'popup_video_path' => $popupVideoPath,
        ]);

        $response = $this->actingAs($user)->delete(
            route('catalog.pdfs.slicer.hotspots.destroy', [$pdf, $hotspot]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('catalog_pdf_hotspots', [
            'id' => $hotspot->id,
        ]);
        $this->assertFalse(Storage::disk('local')->exists($thumbnailPath));
        $this->assertFalse(Storage::disk('local')->exists($popupImagePath));
        $this->assertFalse(Storage::disk('local')->exists($popupVideoPath));
    }

    private function createSlicerPdfForUser(User $user): array
    {
        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Slicer Hotspot PDF',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/slicer-hotspot.pdf',
            'original_filename' => 'slicer-hotspot.pdf',
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
        ]);

        return [$pdf, $page];
    }

    private function baseShapePayload(): array
    {
        return [
            'shape_type' => CatalogPdfHotspot::SHAPE_RECTANGLE,
            'shape_data' => json_encode($this->shapeData(), JSON_THROW_ON_ERROR),
            'x' => '0.100000',
            'y' => '0.200000',
            'w' => '0.300000',
            'h' => '0.150000',
            'is_active' => '1',
            'title' => 'Hotspot',
            'color' => '#123456',
            'link' => '',
            'internal_page_number' => '',
            'description' => '',
            'price' => '',
            'popup_video_url' => '',
        ];
    }

    private function shapeData(): array
    {
        return [
            'type' => 'rect',
            'left' => 98,
            'top' => 144,
            'width' => 220,
            'height' => 96,
            'scaleX' => 1,
            'scaleY' => 1,
            '__meta' => [
                'canvasWidth' => 980,
                'canvasHeight' => 720,
            ],
            'runtimeShape' => [
                'type' => 'rectangle',
                'points' => [
                    ['x' => 0.10, 'y' => 0.20],
                    ['x' => 0.40, 'y' => 0.20],
                    ['x' => 0.40, 'y' => 0.35],
                    ['x' => 0.10, 'y' => 0.35],
                ],
                'bbox' => [
                    'x' => 0.10,
                    'y' => 0.20,
                    'w' => 0.30,
                    'h' => 0.15,
                ],
            ],
        ];
    }
}
