<?php

namespace Tests\Feature\Notifications;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\User;
use App\Notifications\ViewerMilestoneNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ViewerMilestoneNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_crossing_a_view_threshold_sends_a_single_owner_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'customer']);
        $viewer = User::factory()->create();

        $pdf = CatalogPdf::query()->create([
            'user_id' => $owner->id,
            'title' => 'Launch Catalog',
            'template_type' => CatalogPdf::TEMPLATE_PAGE_MANAGEMENT,
            'visibility' => CatalogPdf::VISIBILITY_PUBLIC,
            'storage_disk' => 'public',
            'pdf_path' => 'catalog-pdfs/launch-catalog.pdf',
            'original_filename' => 'launch-catalog.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        CatalogPdfEvent::query()->insert(collect(range(1, 9))->map(fn(int $index) => [
            'catalog_pdf_id' => $pdf->id,
            'user_id' => null,
            'session_id' => 'seed-session-' . $index,
            'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
            'page_number' => 1,
            'catalog_pdf_hotspot_id' => null,
            'meta' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now()->subMinutes(20 - $index),
        ])->all());

        $this->actingAs($viewer)
            ->postJson(route('catalog.pdfs.analytics.track', $pdf), [
                'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
                'page_number' => 1,
            ])
            ->assertOk();

        Notification::assertSentTo($owner, ViewerMilestoneNotification::class, function (ViewerMilestoneNotification $notification) {
            return $notification->threshold() === 10;
        });

        Notification::assertCount(1);
        $this->assertDatabaseHas('notification_deliveries', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'notification_type' => ViewerMilestoneNotification::class,
            'unique_key' => 'viewer:milestone:' . $pdf->id . ':10',
        ]);

        $this->actingAs($viewer)
            ->postJson(route('catalog.pdfs.analytics.track', $pdf), [
                'event_type' => CatalogPdfEvent::EVENT_BOOK_OPEN,
                'page_number' => 1,
            ])
            ->assertOk();

        Notification::assertCount(1);
        $this->assertDatabaseCount('notification_deliveries', 1);
    }
}
