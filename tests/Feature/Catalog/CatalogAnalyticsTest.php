<?php

namespace Tests\Feature\Catalog;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\CatalogPdfPage;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CatalogAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_aggregated_book_analytics(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'customer',
        ]);
        $owner->assignRole('customer');

        $basicPlan = Plan::query()->where('slug', 'basic')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $owner->id,
            'plan_id' => $basicPlan->id,
            'type' => 'default',
            'stripe_id' => 'sub_analytics_owner',
            'stripe_status' => 'active',
            'stripe_price' => $basicPlan->stripe_price_id ?: 'price_basic_analytics',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        /** @var User $readerOne */
        $readerOne = User::factory()->create();
        /** @var User $readerTwo */
        $readerTwo = User::factory()->create();

        $slicerPdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Summer Shoppable',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/summer-shoppable.pdf',
            'original_filename' => 'summer-shoppable.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $flipPdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Physics Showcase',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/physics-showcase.pdf',
            'original_filename' => 'physics-showcase.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $slicerPdf->id,
            'user_id' => $readerOne->id,
            'session_id' => 'reader-one-a',
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
            'page_number' => 1,
            'created_at' => now()->subMinutes(10),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $slicerPdf->id,
            'user_id' => $readerOne->id,
            'session_id' => 'reader-one-a',
            'event_type' => CatalogPdfEvent::EVENT_READING_TIME,
            'page_number' => 2,
            'meta' => ['duration_ms' => 90000],
            'created_at' => now()->subMinutes(9),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $slicerPdf->id,
            'user_id' => $readerOne->id,
            'session_id' => 'reader-one-a',
            'event_type' => CatalogPdfEvent::EVENT_HOTSPOT_CLICK,
            'page_number' => 2,
            'created_at' => now()->subMinutes(8),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $slicerPdf->id,
            'user_id' => $readerTwo->id,
            'session_id' => 'reader-two-a',
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
            'page_number' => 1,
            'created_at' => now()->subMinutes(7),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $slicerPdf->id,
            'user_id' => $readerTwo->id,
            'session_id' => 'reader-two-a',
            'event_type' => CatalogPdfEvent::EVENT_READING_TIME,
            'page_number' => 3,
            'meta' => ['duration_ms' => 30000],
            'created_at' => now()->subMinutes(6),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $flipPdf->id,
            'user_id' => $readerOne->id,
            'session_id' => 'reader-one-b',
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
            'page_number' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        CatalogPdfEvent::create([
            'catalog_pdf_id' => $flipPdf->id,
            'user_id' => $readerOne->id,
            'session_id' => 'reader-one-b',
            'event_type' => CatalogPdfEvent::EVENT_READING_TIME,
            'page_number' => 4,
            'meta' => ['duration_ms' => 60000],
            'created_at' => now()->subMinutes(4),
        ]);

        $response = $this->actingAs($owner)->get(route('analytics.index'));

        $response->assertOk();
        $response->assertViewHas('summary', function (array $summary) {
            return $summary['books_count'] === 2
                && $summary['views_count'] === 3
                && $summary['readers_count'] === 2
                && $summary['time_spent_ms'] === 180000
                && $summary['slice_click_count'] === 1
                && $summary['time_spent_human'] === '3m';
        });

        $response->assertViewHas('books', function (Collection $books) use ($slicerPdf, $flipPdf) {
            $slicer = $books->firstWhere('pdf.id', $slicerPdf->id);
            $flip = $books->firstWhere('pdf.id', $flipPdf->id);

            return $books->count() === 2
                && $slicer !== null
                && $slicer['views_count'] === 2
                && $slicer['readers_count'] === 2
                && $slicer['time_spent_ms'] === 120000
                && $slicer['slice_click_count'] === 1
                && $flip !== null
                && $flip['views_count'] === 1
                && $flip['readers_count'] === 1
                && $flip['time_spent_ms'] === 60000;
        });
    }

    public function test_track_endpoint_records_sanitized_public_viewer_event(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'Customer',
        ]);

        /** @var User $viewer */
        $viewer = User::factory()->create();

        $pdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Tracked Catalog',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/tracked-catalog.pdf',
            'original_filename' => 'tracked-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($viewer)->postJson(route('catalog.pdfs.analytics.track', $pdf), [
            'event_type' => CatalogPdfEvent::EVENT_READING_TIME,
            'page_number' => 3,
            'meta' => [
                'duration_ms' => 999999,
                'source' => 'test',
            ],
        ]);

        $response->assertOk();

        $event = CatalogPdfEvent::query()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame($pdf->id, $event->catalog_pdf_id);
        $this->assertSame($viewer->id, $event->user_id);
        $this->assertSame(CatalogPdfEvent::EVENT_READING_TIME, $event->event_type);
        $this->assertSame(3, $event->page_number);
        $this->assertSame(600000, $event->meta['duration_ms']);
        $this->assertSame('test', $event->meta['source']);
    }

    public function test_share_route_uses_workflow_specific_viewers(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'customer',
        ]);

        $pagePdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Managed Catalog',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/managed-catalog.pdf',
            'original_filename' => 'managed-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        CatalogPdfPage::create([
            'catalog_pdf_id' => $pagePdf->id,
            'page_number' => 1,
            'title' => 'Page 1',
            'display_order' => 1,
        ]);

        $flipPdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Flip Catalog',
            'template_type' => CatalogPdf::TEMPLATE_FLIP_PHYSICS,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/flip-catalog.pdf',
            'original_filename' => 'flip-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $slicerPdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Slicer Catalog',
            'template_type' => CatalogPdf::TEMPLATE_SLICER_SHOPPABLE,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/slicer-catalog.pdf',
            'original_filename' => 'slicer-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $pageResponse = $this->actingAs($owner)->get(route('catalog.pdfs.share', $pagePdf));
        $pageResponse->assertOk();
        $pageResponse->assertViewIs('pages.apps.catalog.page-share');

        $flipResponse = $this->actingAs($owner)->get(route('catalog.pdfs.share', $flipPdf));
        $flipResponse->assertRedirect(route('catalog.pdfs.flip-physics.share', $flipPdf));

        $slicerResponse = $this->actingAs($owner)->get(route('catalog.pdfs.share', $slicerPdf));
        $slicerResponse->assertRedirect(route('catalog.pdfs.slicer.share', $slicerPdf));
    }

    public function test_admin_can_track_private_catalog_event(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
        ]);

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'customer',
        ]);

        $pdf = CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Private Catalog',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PRIVATE,
            'storage_disk' => 'local',
            'pdf_path' => 'catalog-pdfs/private-catalog.pdf',
            'original_filename' => 'private-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->actingAs($admin)
            ->postJson(route('catalog.pdfs.analytics.track', $pdf), [
                'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
                'page_number' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('catalog_pdf_events', [
            'catalog_pdf_id' => $pdf->id,
            'user_id' => $admin->id,
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
        ]);
    }

    public function test_admin_can_open_analytics_page(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => 'customer',
            'name' => 'Catalog Owner',
            'email' => 'owner@example.com',
        ]);
        $owner->assignRole('customer');

        CatalogPdf::create([
            'user_id' => $owner->id,
            'title' => 'Admin Visible Catalog',
            'template_type' => CatalogPdf::TEMPLATE_UPLOADED,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/admin-visible.pdf',
            'original_filename' => 'admin-visible.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->actingAs($admin)->get(route('analytics.index'));

        $response->assertOk();
        $response->assertSee('Catalog Analytics Overview');
        $response->assertSee('Admin Visible Catalog');
        $response->assertSee('Catalog Owner');
    }

    public function test_customer_without_customer_role_cannot_open_analytics_page(): void
    {
        $this->seed([
            ProjectPermissionsSeeder::class,
            BillingSeeder::class,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('analytics.index'));

        $response->assertForbidden();
    }
}
