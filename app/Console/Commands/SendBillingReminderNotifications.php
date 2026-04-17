<?php

namespace App\Console\Commands;

use App\Services\Notifications\BillingNotificationService;
use Illuminate\Console\Command;

class SendBillingReminderNotifications extends Command
{
    protected $signature = 'notifications:billing-reminders {--days=7,3,1 : Comma-separated reminder day offsets}';

    protected $description = 'Send billing reminder notifications for upcoming renewals and trial endings.';

    public function handle(BillingNotificationService $billingNotificationService): int
    {
        $dayOffsets = collect(explode(',', (string) $this->option('days')))
            ->map(fn(string $days) => trim($days))
            ->filter(fn(string $days) => $days !== '')
            ->map(fn(string $days) => (int) $days)
            ->values()
            ->all();

        $results = $billingNotificationService->sendScheduledReminders($dayOffsets);

        $this->info(sprintf(
            'Billing reminders sent: %d upcoming payment, %d trial ending.',
            $results['upcoming_payment'],
            $results['trial_ending']
        ));

        return self::SUCCESS;
    }
}
