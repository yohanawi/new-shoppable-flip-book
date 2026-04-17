<?php

namespace Tests\Feature\Billing;

use App\Models\CatalogPdf;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogBillingLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_blocks_creating_more_than_two_flipbooks(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seed(BillingSeeder::class);

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

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

        $response = $this->from(route('catalog.pdfs.create'))
            ->actingAs($user)
            ->post(route('catalog.pdfs.store'), [
                'title' => 'Blocked Book',
                'description' => 'Should be blocked by billing.',
                'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
                'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
                'pdf' => UploadedFile::fake()->create('blocked-book.pdf', 256, 'application/pdf'),
            ]);

        $response->assertRedirect(route('catalog.pdfs.create'));
        $response->assertSessionHasErrors('billing');

        $this->assertDatabaseMissing('catalog_pdfs', [
            'title' => 'Blocked Book',
        ]);
    }
}
