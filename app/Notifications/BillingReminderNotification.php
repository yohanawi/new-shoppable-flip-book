<?php

namespace App\Notifications;

use App\Models\BillingInvoice;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class BillingReminderNotification extends RealtimeDatabaseNotification
{
    use Queueable;

    public const TYPE_UPCOMING_PAYMENT = 'upcoming_payment';
    public const TYPE_FAILED_PAYMENT = 'failed_payment';
    public const TYPE_TRIAL_ENDING = 'trial_ending';
    public const TYPE_INVOICE_AVAILABLE = 'invoice_available';

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $actionUrl = null,
        private readonly ?string $actionText = null,
        private readonly array $meta = [],
        private readonly ?BillingInvoice $invoice = null,
        private readonly ?Subscription $subscription = null,
    ) {}

    protected function additionalChannels(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message);

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line('You can manage billing any time from your account dashboard.');
    }

    protected function notificationData(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'invoice_id' => $this->invoice?->id,
            'subscription_id' => $this->subscription?->id,
            'meta' => $this->meta,
        ];
    }

    public function type(): string
    {
        return $this->type;
    }
}
