<?php

namespace App\Notifications;

use App\Models\BillingPaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class BillingPaymentRequestNotification extends RealtimeDatabaseNotification
{
    use Queueable;

    public const TYPE_SUBMITTED = 'payment_request_submitted';
    public const TYPE_RESUBMITTED = 'payment_request_resubmitted';
    public const TYPE_APPROVED = 'payment_request_approved';
    public const TYPE_REJECTED = 'payment_request_rejected';

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly BillingPaymentRequest $paymentRequest,
        private readonly ?string $actionUrl = null,
        private readonly ?string $actionText = null,
        private readonly array $meta = [],
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

        return $mail->line('You can review the latest billing status in your account dashboard.');
    }

    protected function notificationData(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'payment_request_id' => $this->paymentRequest->id,
            'meta' => $this->meta,
        ];
    }
}
