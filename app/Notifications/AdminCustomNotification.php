<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class AdminCustomNotification extends RealtimeDatabaseNotification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $actionUrl = null,
        private readonly ?string $actionText = null,
    ) {}

    protected function additionalChannels(object $notifiable): array
    {
        $channels = [];

        if ($this->shouldSendMail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
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

        return $mail;
    }

    protected function notificationData(object $notifiable): array
    {
        return [
            'type' => 'admin_custom',
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
        ];
    }

    private function shouldSendMail(object $notifiable): bool
    {
        if (!filled($notifiable->email ?? null)) {
            return false;
        }

        $defaultMailer = config('mail.default');

        if (!is_string($defaultMailer) || $defaultMailer === '') {
            return false;
        }

        return $this->mailerIsConfigured($defaultMailer);
    }

    private function mailerIsConfigured(string $mailer, array $checked = []): bool
    {
        if (in_array($mailer, $checked, true)) {
            return false;
        }

        $config = config("mail.mailers.{$mailer}");

        if (!is_array($config)) {
            return false;
        }

        $transport = $config['transport'] ?? null;

        return match ($transport) {
            'array', 'log' => true,
            'smtp' => filled($config['host'] ?? null),
            'sendmail' => filled($config['path'] ?? null),
            'failover' => collect($config['mailers'] ?? [])
                ->contains(fn($nestedMailer) => is_string($nestedMailer)
                    && $this->mailerIsConfigured($nestedMailer, [...$checked, $mailer])),
            default => false,
        };
    }
}
