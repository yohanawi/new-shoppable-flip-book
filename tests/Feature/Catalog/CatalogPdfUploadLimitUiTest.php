<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPdfUploadLimitUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_marks_upload_cta_as_blocked_when_flipbook_limit_is_reached(): void
    {
        $this->seed(BillingSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();

        CatalogPdf::query()->create([
            'user_id' => $user->id,
            'title' => 'Book 1',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/book-1.pdf',
            'original_filename' => 'book-1.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        CatalogPdf::query()->create([
            'user_id' => $user->id,
            'title' => 'Book 2',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/book-2.pdf',
            'original_filename' => 'book-2.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.pdfs.index'));

        $response->assertOk();
        $response->assertSee('data-upload-blocked="true"', false);
        $response->assertSee('You have reached your flipbook limit for the current plan.');
    }

    public function test_catalog_create_redirects_back_to_index_when_flipbook_limit_is_reached(): void
    {
        $this->seed(BillingSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();

        CatalogPdf::query()->create([
            'user_id' => $user->id,
            'title' => 'Book 1',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/book-1.pdf',
            'original_filename' => 'book-1.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        CatalogPdf::query()->create([
            'user_id' => $user->id,
            'title' => 'Book 2',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/book-2.pdf',
            'original_filename' => 'book-2.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.pdfs.create'));

        $response->assertRedirect(route('catalog.pdfs.index'));
        $response->assertSessionHasErrors('billing');
    }
}
