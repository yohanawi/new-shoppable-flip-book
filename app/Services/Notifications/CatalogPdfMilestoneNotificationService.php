<?php

namespace App\Services\Notifications;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Notifications\ViewerMilestoneNotification;

class CatalogPdfMilestoneNotificationService
{
    private const VIEW_THRESHOLDS = [10, 50, 100, 250, 500, 1000];

    public function __construct(private readonly NotificationDispatchService $dispatchService) {}

    public function handleTrackedEvent(CatalogPdf $catalogPdf, string $eventType): int
    {
        if ($eventType !== CatalogPdfEvent::EVENT_BOOK_OPEN) {
            return 0;
        }

        return $this->sendViewMilestones($catalogPdf);
    }

    public function sendViewMilestones(CatalogPdf $catalogPdf): int
    {
        $catalogPdf->loadMissing('user');

        if (!$catalogPdf->user) {
            return 0;
        }

        $viewsCount = (int) $catalogPdf->events()
            ->where('event_type', CatalogPdfEvent::EVENT_BOOK_OPEN)
            ->count();

        $sentCount = 0;

        foreach ($this->thresholdsReached($viewsCount) as $threshold) {
            $sent = $this->dispatchService->sendOnce(
                $catalogPdf->user,
                new ViewerMilestoneNotification($catalogPdf, $threshold, $viewsCount),
                sprintf('viewer:milestone:%s:%s', $catalogPdf->id, $threshold),
                [
                    'catalog_pdf_id' => $catalogPdf->id,
                    'threshold' => $threshold,
                    'views_count' => $viewsCount,
                ]
            );

            if ($sent) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function thresholdsReached(int $viewsCount): array
    {
        return array_values(array_filter(
            self::VIEW_THRESHOLDS,
            fn(int $threshold) => $viewsCount >= $threshold
        ));
    }
}
