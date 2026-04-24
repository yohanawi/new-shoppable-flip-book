<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdfPage;
use App\Models\CatalogPdf;
use App\Models\User;
use App\Services\CatalogPdfSlicerImageGenerator;
use App\Services\PdfPageCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CatalogPdfUploadWorkflowSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_without_template_creates_uploaded_pdf_and_redirects_to_detail_page(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('catalog.pdfs.store'), [
            'title' => 'Base Upload',
            'description' => 'Upload first, configure later',
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'pdf' => UploadedFile::fake()->create('base-upload.pdf', 256, 'application/pdf'),
        ]);

        $pdf = CatalogPdf::query()->where('title', 'Base Upload')->firstOrFail();

        $response->assertRedirect(route('catalog.pdfs.show', $pdf));
        $response->assertSessionHas('success', 'PDF uploaded successfully. Choose a workflow to continue.');

        $this->assertSame(CatalogPdf::TEMPLATE_UPLOADED, $pdf->template_type);
        $this->assertTrue(Storage::disk('local')->exists($pdf->pdf_path));

        $showResponse = $this->actingAs($user)->get(route('catalog.pdfs.show', $pdf));
        $showResponse->assertOk();
        $showResponse->assertSee('Use all functions on this PDF');
        $showResponse->assertSee('Open any tool below. They all work on the same uploaded file.');
        $showResponse->assertSee(route('catalog.pdfs.manage', $pdf), false);
        $showResponse->assertSee(route('catalog.pdfs.flip-physics.edit', $pdf), false);
        $showResponse->assertSee(route('catalog.pdfs.slicer.edit', $pdf), false);
    }

    public function test_customer_can_select_flip_physics_after_uploading_pdf(): void
    {
        Storage::fake('local');

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Workflow Ready PDF',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/workflow-ready.pdf',
            'original_filename' => 'workflow-ready.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $response = $this->actingAs($user)->post(route('catalog.pdfs.workflow.select', $pdf), [
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
        ]);

        $response->assertRedirect(route('catalog.pdfs.flip-physics.edit', $pdf));
        $response->assertSessionHas('success', 'Workflow selected successfully.');

        $this->assertDatabaseHas('catalog_pdfs', [
            'id' => $pdf->id,
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
        ]);

        $editResponse = $this->actingAs($user)->get(route('catalog.pdfs.flip-physics.edit', $pdf));
        $editResponse->assertOk();
        $editResponse->assertSee('Realistic (default)');
    }

    public function test_customer_can_select_page_management_after_uploading_pdf(): void
    {
        Storage::fake('local');

        app()->instance(PdfPageCounter::class, new class {
            public function count(string $path): int
            {
                return 3;
            }
        });

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Page Management PDF',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/page-management.pdf',
            'original_filename' => 'page-management.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $response = $this->actingAs($user)->post(route('catalog.pdfs.workflow.select', $pdf), [
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
        ]);

        $response->assertRedirect(route('catalog.pdfs.manage', $pdf));

        $this->assertDatabaseHas('catalog_pdfs', [
            'id' => $pdf->id,
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
        ]);

        $this->assertSame(3, CatalogPdfPage::query()->where('catalog_pdf_id', $pdf->id)->count());
    }

    public function test_customer_can_select_slicer_after_uploading_pdf(): void
    {
        Storage::fake('local');

        app()->instance(PdfPageCounter::class, new class {
            public function count(string $path): int
            {
                return 2;
            }
        });

        $imageGenerator = Mockery::mock(CatalogPdfSlicerImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->once();
        app()->instance(CatalogPdfSlicerImageGenerator::class, $imageGenerator);

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'Slicer PDF',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/slicer.pdf',
            'original_filename' => 'slicer.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $response = $this->actingAs($user)->post(route('catalog.pdfs.workflow.select', $pdf), [
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
        ]);

        $response->assertRedirect(route('catalog.pdfs.slicer.edit', $pdf));

        $this->assertDatabaseHas('catalog_pdfs', [
            'id' => $pdf->id,
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
        ]);

        $this->assertSame(2, CatalogPdfPage::query()->where('catalog_pdf_id', $pdf->id)->count());
    }

    public function test_same_pdf_can_open_all_three_functions_even_after_focus_changes(): void
    {
        Storage::fake('local');

        app()->instance(PdfPageCounter::class, new class {
            public function count(string $path): int
            {
                return 2;
            }
        });

        $imageGenerator = Mockery::mock(CatalogPdfSlicerImageGenerator::class);
        $imageGenerator->shouldReceive('generate')->zeroOrMoreTimes();
        app()->instance(CatalogPdfSlicerImageGenerator::class, $imageGenerator);

        /** @var User $user */
        $user = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $user->id,
            'title' => 'All Features PDF',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/all-features.pdf',
            'original_filename' => 'all-features.pdf',
            'mime_type' => 'application/pdf',
            'size' => 128,
        ]);

        Storage::disk('local')->put($pdf->pdf_path, "%PDF-test\n");

        $focusResponse = $this->actingAs($user)->post(route('catalog.pdfs.workflow.select', $pdf), [
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
        ]);

        $focusResponse->assertRedirect(route('catalog.pdfs.flip-physics.edit', $pdf));

        $this->actingAs($user)->get(route('catalog.pdfs.manage', $pdf))->assertOk();
        $this->actingAs($user)->get(route('catalog.pdfs.flip-physics.edit', $pdf))->assertOk();
        $this->actingAs($user)->get(route('catalog.pdfs.slicer.edit', $pdf))->assertOk();
    }
}
