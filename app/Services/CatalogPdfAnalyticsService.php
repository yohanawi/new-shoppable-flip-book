<?php

namespace App\Services;

use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CatalogPdfAnalyticsService
{
    public function forViewer(User $viewer, ?User $owner = null): array
    {
        $pdfs = CatalogPdf::query()
            ->with('user')
            ->when(
                !$viewer->isAdmin() || $owner !== null,
                fn(Builder $query) => $query->where('user_id', ($owner ?: $viewer)->id)
            )
            ->latest()
            ->get();

        $events = CatalogPdfEvent::query()
            ->whereIn('catalog_pdf_id', $pdfs->pluck('id'))
            ->orderBy('created_at')
            ->get();

        $eventsByPdf = $events->groupBy('catalog_pdf_id');
        $allReaderKeys = collect();

        $books = $pdfs->map(function (CatalogPdf $pdf) use ($eventsByPdf, $allReaderKeys) {
            $pdfEvents = $eventsByPdf->get($pdf->id, collect());
            $readerKeys = $this->readerKeys($pdfEvents);
            $lastViewedAt = $pdfEvents->sortByDesc('created_at')->first()?->created_at;
            $timeSpentMs = $this->timeSpentMs($pdfEvents);

            return [
                'pdf' => $pdf,
                'owner' => $pdf->user,
                'views_count' => $pdfEvents->where('event_type', CatalogPdfEvent::EVENT_BOOK_OPEN)->count(),
                'readers_count' => $readerKeys->count(),
                'reader_keys' => $readerKeys,
                'time_spent_ms' => $timeSpentMs,
                'time_spent_human' => $this->formatDurationMs($timeSpentMs),
                'slice_click_count' => $pdfEvents->where('event_type', CatalogPdfEvent::EVENT_HOTSPOT_CLICK)->count(),
                'last_viewed_at' => $lastViewedAt,
                'share_url' => $this->shareUrlForPdf($pdf),
                'manage_url' => $this->manageUrlForPdf($pdf),
            ];
        })->map(function (array $book) use (&$allReaderKeys) {
            $allReaderKeys = $allReaderKeys->merge($book['reader_keys']);
            unset($book['reader_keys']);

            return $book;
        })->values();

        return [
            'summary' => [
                'books_count' => $books->count(),
                'owners_count' => $books->pluck('owner.id')->filter()->unique()->count(),
                'views_count' => $books->sum('views_count'),
                'readers_count' => $allReaderKeys->filter()->unique()->count(),
                'time_spent_ms' => $books->sum('time_spent_ms'),
                'time_spent_human' => $this->formatDurationMs((int) $books->sum('time_spent_ms')),
                'slice_click_count' => $books->sum('slice_click_count'),
            ],
            'books' => $books,
        ];
    }

    private function readerKeys(Collection $events): Collection
    {
        return $events->map(function (CatalogPdfEvent $event) {
            if ($event->user_id) {
                return 'user:' . $event->user_id;
            }

            if ($event->session_id) {
                return 'session:' . $event->session_id;
            }

            if ($event->ip) {
                return 'ip:' . $event->ip;
            }

            return null;
        })->filter()->unique()->values();
    }

    private function timeSpentMs(Collection $events): int
    {
        return (int) $events
            ->where('event_type', CatalogPdfEvent::EVENT_READING_TIME)
            ->sum(function (CatalogPdfEvent $event) {
                $durationMs = (int) data_get($event->meta, 'duration_ms', 0);

                return max(0, min($durationMs, 600000));
            });
    }

    private function shareUrlForPdf(CatalogPdf $pdf): string
    {
        return route('catalog.pdfs.share', $pdf);
    }

    private function manageUrlForPdf(CatalogPdf $pdf): string
    {
        if ($pdf->isPageManagementTemplate()) {
            return route('catalog.pdfs.manage', $pdf);
        }

        if ($pdf->isFlipPhysicsTemplate()) {
            return route('catalog.pdfs.flip-physics.edit', $pdf);
        }

        if ($pdf->isUploadedTemplate()) {
            return route('catalog.pdfs.show', $pdf);
        }

        return route('catalog.pdfs.slicer.edit', $pdf);
    }

    private function formatDurationMs(int $milliseconds): string
    {
        $totalSeconds = max(0, (int) floor($milliseconds / 1000));

        $days = intdiv($totalSeconds, 86400);
        $hours = intdiv($totalSeconds % 86400, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }

        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }

        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }

        if ($seconds > 0 || $parts === []) {
            $parts[] = $seconds . 's';
        }

        return implode(' ', array_slice($parts, 0, 3));
    }
}
