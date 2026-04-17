<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\User;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPdfOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_catalog_index_only_lists_their_own_pdfs(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $owner = User::factory()->create(['role' => 'customer']);
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherCustomer->assignRole('customer');

        $ownedPdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Owner Catalog',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/owner-catalog.pdf',
            'original_filename' => 'owner-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        CatalogPdf::create([
            'user_id' => $otherCustomer->id,
            'title' => 'Other Catalog',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/other-catalog.pdf',
            'original_filename' => 'other-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        $response = $this->actingAs($owner)->get(route('catalog.pdfs.index'));

        $response->assertOk();
        $response->assertSee($ownedPdf->title);
        $response->assertDontSee('Other Catalog');
    }

    public function test_customer_cannot_open_another_customers_public_pdf_in_management_routes(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        $owner = User::factory()->create(['role' => 'customer']);
        $owner->assignRole('customer');

        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherCustomer->assignRole('customer');

        $pdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Public Owner Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/public-owner-catalog.pdf',
            'original_filename' => 'public-owner-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        $this->actingAs($otherCustomer)
            ->get(route('catalog.pdfs.show', $pdf))
            ->assertForbidden();

        $this->actingAs($otherCustomer)
            ->get(route('catalog.pdfs.flip-physics.edit', $pdf))
            ->assertForbidden();
    }
}
