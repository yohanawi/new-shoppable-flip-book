<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class NotificationDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_notification_once_and_records_the_delivery(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();
        $service = app(NotificationDispatchService::class);

        $sent = $service->sendOnce(
            $user,
            new TestNotification('First notification'),
            'billing:invoice_available:inv_123',
            ['invoice_id' => 'inv_123']
        );

        $this->assertTrue($sent);
        NotificationFacade::assertSentTo($user, TestNotification::class);
        NotificationFacade::assertCount(1);

        $this->assertDatabaseHas('notification_deliveries', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'notification_type' => TestNotification::class,
            'unique_key' => 'billing:invoice_available:inv_123',
        ]);
    }

    public function test_it_skips_duplicate_notification_keys_for_the_same_user(): void
    {
        NotificationFacade::fake();

        $user = User::factory()->create();
        $service = app(NotificationDispatchService::class);

        $firstSend = $service->sendOnce(
            $user,
            new TestNotification('Duplicate-safe notification'),
            'viewer:milestone:book_44:100',
            ['catalog_pdf_id' => 44, 'threshold' => 100]
        );

        $secondSend = $service->sendOnce(
            $user,
            new TestNotification('Duplicate-safe notification'),
            'viewer:milestone:book_44:100',
            ['catalog_pdf_id' => 44, 'threshold' => 100]
        );

        $this->assertTrue($firstSend);
        $this->assertFalse($secondSend);
        NotificationFacade::assertSentTo($user, TestNotification::class);
        NotificationFacade::assertCount(1);
        $this->assertSame(1, NotificationDelivery::query()->count());
    }
}

class TestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $message) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Test notification')
            ->line($this->message);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
