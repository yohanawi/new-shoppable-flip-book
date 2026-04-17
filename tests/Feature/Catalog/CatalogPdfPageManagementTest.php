<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogPdfPageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_changes_applies_delete_reorder_hide_and_lock_rules(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Spring Catalog',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/test-catalog.pdf',
            'original_filename' => 'test-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $firstPage = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 1,
            'display_order' => 1,
            'title' => 'Page 1',
            'is_locked' => false,
            'is_hidden' => false,
        ]);

        $lockedPage = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 2,
            'display_order' => 2,
            'title' => 'Locked Page',
            'is_locked' => true,
            'is_hidden' => false,
        ]);

        $thirdPage = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 3,
            'display_order' => 3,
            'title' => 'Page 3',
            'is_locked' => false,
            'is_hidden' => false,
        ]);

        $deletedPage = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 4,
            'display_order' => 4,
            'title' => 'Page 4',
            'is_locked' => false,
            'is_hidden' => false,
        ]);

        $response = $this->actingAs($user)->post(route('catalog.pdfs.manage.update', $pdf), [
            'pages' => [
                $firstPage->id => [
                    'title' => 'Cover',
                    'display_order' => 3,
                    'is_hidden' => '1',
                    'is_locked' => '0',
                    'is_deleted' => '0',
                ],
                $lockedPage->id => [
                    'title' => 'Should Not Change',
                    'display_order' => 1,
                    'is_hidden' => '1',
                    'is_locked' => '1',
                    'is_deleted' => '1',
                ],
                $thirdPage->id => [
                    'title' => 'Introduction',
                    'display_order' => 1,
                    'is_hidden' => '0',
                    'is_locked' => '0',
                    'is_deleted' => '0',
                ],
                $deletedPage->id => [
                    'title' => 'Remove Me',
                    'display_order' => 2,
                    'is_hidden' => '0',
                    'is_locked' => '0',
                    'is_deleted' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('catalog.pdfs.manage', $pdf));

        $this->assertSoftDeleted('catalog_pdf_pages', [
            'id' => $deletedPage->id,
        ]);

        $this->assertDatabaseHas('catalog_pdf_pages', [
            'id' => $firstPage->id,
            'title' => 'Cover',
            'is_hidden' => true,
            'display_order' => 3,
        ]);

        $this->assertDatabaseHas('catalog_pdf_pages', [
            'id' => $lockedPage->id,
            'title' => 'Locked Page',
            'is_hidden' => false,
            'is_locked' => true,
            'display_order' => 2,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('catalog_pdf_pages', [
            'id' => $thirdPage->id,
            'title' => 'Introduction',
            'display_order' => 1,
        ]);
    }

    public function test_save_changes_requires_at_least_one_visible_page(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Visibility Rules',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/visibility-rules.pdf',
            'original_filename' => 'visibility-rules.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $page = CatalogPdfPage::create([
            'catalog_pdf_id' => $pdf->id,
            'page_number' => 1,
            'display_order' => 1,
            'title' => 'Only Page',
            'is_locked' => false,
            'is_hidden' => false,
        ]);

        $response = $this->from(route('catalog.pdfs.manage', $pdf))
            ->actingAs($user)
            ->post(route('catalog.pdfs.manage.update', $pdf), [
                'pages' => [
                    $page->id => [
                        'title' => 'Only Page',
                        'display_order' => 1,
                        'is_hidden' => '1',
                        'is_locked' => '0',
                        'is_deleted' => '0',
                    ],
                ],
            ]);

        $response->assertRedirect(route('catalog.pdfs.manage', $pdf));
        $response->assertSessionHasErrors('pages');

        $this->assertDatabaseHas('catalog_pdf_pages', [
            'id' => $page->id,
            'is_hidden' => false,
        ]);
    }
}
