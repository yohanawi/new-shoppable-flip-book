<?php

namespace App\Notifications;

use App\Models\CatalogPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ViewerMilestoneNotification extends RealtimeDatabaseNotification
{
    use Queueable;

    public function __construct(
        private readonly CatalogPdf $catalogPdf,
        private readonly int $threshold,
        private readonly int $viewsCount,
    ) {}

    protected function additionalChannels(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->catalogPdf->title . ' reached ' . number_format($this->threshold) . ' views')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line(sprintf(
                'Your flipbook "%s" just crossed %s total views and is now at %s views.',
                $this->catalogPdf->title,
                number_format($this->threshold),
                number_format($this->viewsCount)
            ))
            ->action('Open Flipbook', route('catalog.pdfs.show', $this->catalogPdf))
            ->line('Keep publishing and sharing to build more reach.');
    }

    protected function notificationData(object $notifiable): array
    {
        return [
            'type' => 'viewer_milestone',
            'title' => $this->catalogPdf->title . ' reached ' . number_format($this->threshold) . ' views',
            'message' => sprintf(
                'Your flipbook "%s" just crossed %s total views and is now at %s views.',
                $this->catalogPdf->title,
                number_format($this->threshold),
                number_format($this->viewsCount)
            ),
            'action_url' => route('catalog.pdfs.show', $this->catalogPdf),
            'action_text' => 'Open Flipbook',
            'catalog_pdf_id' => $this->catalogPdf->id,
            'threshold' => $this->threshold,
            'views_count' => $this->viewsCount,
        ];
    }

    public function threshold(): int
    {
        return $this->threshold;
    }
}
