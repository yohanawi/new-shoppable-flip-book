<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPdfIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_search_and_filter_their_catalog_index(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherCustomer->assignRole('customer');

        CatalogPdf::create([
            'user_id' => $customer->id,
            'title' => 'Winter Launch Deck',
            'description' => 'Main seasonal shoppable catalog.',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/winter-launch-deck.pdf',
            'original_filename' => 'winter-launch.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        CatalogPdf::create([
            'user_id' => $customer->id,
            'title' => 'Internal Review Notes',
            'description' => 'Private document for approvals.',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/internal-review-notes.pdf',
            'original_filename' => 'review-notes.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        CatalogPdf::create([
            'user_id' => $otherCustomer->id,
            'title' => 'Winter Launch Deck External',
            'description' => 'Another user record.',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/external-winter-launch.pdf',
            'original_filename' => 'external-winter-launch.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($customer)->get(route('catalog.pdfs.index', [
            'search' => 'Winter',
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'sort' => 'title_asc',
        ]));

        $response->assertOk();
        $response->assertSee('Winter Launch Deck');
        $response->assertDontSee('Internal Review Notes');
        $response->assertDontSee('Winter Launch Deck External');
        $response->assertSee('Search: Winter');
        $response->assertSee('Visibility: Public');
    }
}
