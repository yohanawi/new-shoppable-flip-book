<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\AdminCustomNotification;
use Database\Seeders\ProjectPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_and_mark_their_notifications_as_read(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['role' => 'customer']);
        $user->assignRole('customer');
        $user->notify(new AdminCustomNotification('Account update', 'Your notification inbox is active.'));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Account update');

        $notificationId = $user->fresh()->notifications()->firstOrFail()->id;

        $this->actingAs($user)
            ->post(route('notifications.read', $notificationId))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }

    public function test_customer_can_fetch_the_notification_feed_as_json(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        /** @var User $user */
        $user = User::factory()->create(['role' => 'customer']);
        $user->assignRole('customer');
        $user->notify(new AdminCustomNotification('Realtime inbox', 'Feed payload should be returned as JSON.'));

        $response = $this->actingAs($user)
            ->getJson(route('notifications.feed'));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonPath('notifications.0.title', 'Realtime inbox');
        $response->assertJsonPath('notifications.0.message', 'Feed payload should be returned as JSON.');
    }

    public function test_admin_can_send_custom_notifications_and_view_the_audit_page(): void
    {
        $this->seed(ProjectPermissionsSeeder::class);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => null,
        ]);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $recipient */
        $recipient = User::factory()->create(['role' => 'customer']);
        $recipient->assignRole('customer');

        $this->actingAs($admin)
            ->post(route('admin.notifications.send'), [
                'user_ids' => [$recipient->id],
                'title' => 'Admin notice',
                'message' => 'Please review the latest billing update.',
                'action_url' => route('billing.index'),
                'action_text' => 'Open Billing',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'type' => AdminCustomNotification::class,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Admin notice');
        $response->assertSee($recipient->email);
    }
}
