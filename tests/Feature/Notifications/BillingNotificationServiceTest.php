<?php

namespace Tests\Feature\Notifications;

use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\BillingReminderNotification;
use App\Services\Notifications\BillingNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_notifications_are_sent_once_per_invoice(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'customer']);
        $plan = $this->makePlan();
        $subscription = $this->makeSubscription($user, $plan, ['stripe_status' => 'active']);
        $invoice = BillingInvoice::query()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => 'in_test_available',
            'number' => 'INV-1001',
            'currency' => 'usd',
            'amount_due' => 2999,
            'amount_paid' => 0,
            'status' => 'open',
            'period_end' => now()->addDays(7),
        ]);

        $service = app(BillingNotificationService::class);

        $firstSend = $service->sendInvoiceAvailable($invoice);
        $secondSend = $service->sendInvoiceAvailable($invoice);
        $failedSend = $service->sendFailedPayment($invoice->forceFill([
            'status' => 'failed',
            'stripe_invoice_id' => 'in_test_failed',
        ]));

        $this->assertTrue($firstSend);
        $this->assertFalse($secondSend);
        $this->assertTrue($failedSend);
        Notification::assertCount(2);
    }

    public function test_scheduled_billing_reminders_are_sent_once_for_due_trials_and_upcoming_payments(): void
    {
        $upcomingUser = User::factory()->create(['role' => 'customer']);
        $trialUser = User::factory()->create(['role' => 'customer']);
        $plan = $this->makePlan();

        $upcomingSubscription = $this->makeSubscription($upcomingUser, $plan, [
            'stripe_status' => 'active',
            'trial_ends_at' => null,
        ]);

        BillingInvoice::query()->create([
            'user_id' => $upcomingUser->id,
            'subscription_id' => $upcomingSubscription->id,
            'stripe_invoice_id' => 'in_upcoming',
            'number' => 'INV-UPCOMING',
            'currency' => 'usd',
            'amount_due' => 4900,
            'amount_paid' => 4900,
            'status' => 'paid',
            'period_end' => now()->addDays(7),
            'paid_at' => now()->subDays(23),
        ]);

        $this->makeSubscription($trialUser, $plan, [
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $service = app(BillingNotificationService::class);

        $results = $service->sendScheduledReminders([7, 3]);

        $this->assertSame(['upcoming_payment' => 1, 'trial_ending' => 1], $results);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'type' => BillingReminderNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $upcomingUser->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => BillingReminderNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $trialUser->id,
        ]);

        $results = $service->sendScheduledReminders([7, 3]);

        $this->assertSame(['upcoming_payment' => 0, 'trial_ending' => 0], $results);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('notification_deliveries', 2);
    }

    public function test_billing_reminder_command_delegates_to_the_service(): void
    {
        $this->mock(BillingNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendScheduledReminders')
                ->once()
                ->with([7, 3])
                ->andReturn([
                    'upcoming_payment' => 2,
                    'trial_ending' => 1,
                ]);
        });

        $this->artisan('notifications:billing-reminders', ['--days' => '7,3'])
            ->expectsOutput('Billing reminders sent: 2 upcoming payment, 1 trial ending.')
            ->assertSuccessful();
    }

    private function makePlan(): Plan
    {
        return Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic-test',
            'description' => 'Test plan',
            'price' => 49,
            'currency' => 'usd',
            'interval' => 'month',
            'trial_days' => 14,
            'limits' => ['flipbooks' => 10, 'storage_mb' => 512],
            'features' => ['analytics' => true, 'branding' => false],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeSubscription(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::query()->create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => 'sub_' . str()->random(12),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ], $overrides));
    }
}
