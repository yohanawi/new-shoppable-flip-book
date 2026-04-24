<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly BillingManager $billingManager,
        private readonly CatalogPdfAnalyticsService $analyticsService,
    ) {}

    public function buildFor(User $user): array
    {
        $analytics = $this->analyticsService->forViewer($user);
        $books = collect($analytics['books'] ?? []);
        $summary = $analytics['summary'] ?? [];

        $pdfIds = $books
            ->map(fn(array $book) => data_get($book, 'pdf.id'))
            ->filter()
            ->values();

        $events = CatalogPdfEvent::query()
            ->whereIn('catalog_pdf_id', $pdfIds)
            ->orderBy('created_at')
            ->get();

        $eventsByPdf = $events->groupBy('catalog_pdf_id');
        $viewTrend = $this->trend($events, CatalogPdfEvent::EVENT_BOOK_OPEN);
        $readingTrend = $this->trend($events, CatalogPdfEvent::EVENT_READING_TIME, valueResolver: function (CatalogPdfEvent $event): int {
            return max(0, min((int) data_get($event->meta, 'duration_ms', 0), 600000));
        });
        $clickTrend = $this->trend($events, CatalogPdfEvent::EVENT_HOTSPOT_CLICK);

        $catalogRows = $books
            ->map(function (array $book) use ($eventsByPdf, $user) {
                /** @var CatalogPdf|null $pdf */
                $pdf = data_get($book, 'pdf');
                $pdfEvents = $eventsByPdf->get((int) data_get($book, 'pdf.id'), collect());
                $viewsCount = (int) data_get($book, 'views_count', 0);

                return [
                    'name' => (string) ($pdf?->title ?: 'Untitled catalog'),
                    'owner' => (string) data_get($book, 'owner.name', 'Unknown owner'),
                    'views' => $viewsCount,
                    'avg_time' => $this->formatDurationMs($viewsCount > 0 ? (int) floor(((int) data_get($book, 'time_spent_ms', 0)) / $viewsCount) : 0),
                    'slice_clicks' => (int) data_get($book, 'slice_click_count', 0),
                    'completion_rate' => $this->completionRate($pdfEvents),
                    'status' => $pdf?->visibility === CatalogPdf::VISIBILITY_PUBLIC ? 'Public' : 'Private',
                    'status_tone' => $pdf?->visibility === CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'secondary',
                    'template' => CatalogPdf::templateTypeOptions()[$pdf?->template_type ?? ''] ?? 'Uploaded PDF',
                    'action_label' => $user->isAdmin() ? 'Open Workspace' : 'Manage',
                    'action_url' => (string) data_get($book, 'manage_url', route('catalog.pdfs.index')),
                ];
            })
            ->sortByDesc('views')
            ->take(8)
            ->values();

        $shared = [
            'dashboardRole' => $user->isAdmin() ? 'admin' : 'customer',
            'summary' => $summary,
            'chart' => [
                'title' => $user->isAdmin() ? 'Platform Views Trend' : 'Reader Views Trend',
                'subtitle' => $user->isAdmin()
                    ? 'Book opens captured across the last 7 days'
                    : 'How readers interacted with your flipbooks during the last 7 days',
                'value' => number_format(array_sum($viewTrend['points'])),
                'value_caption' => $viewTrend['comparison']['label'] . ' vs previous 7 days',
                'points' => $viewTrend['points'],
                'labels' => $viewTrend['labels'],
            ],
            'catalogTable' => [
                'title' => $user->isAdmin() ? 'Catalog Analytics By Workspace' : 'Analytics By Catalog',
                'subtitle' => $user->isAdmin()
                    ? 'Top-performing flipbooks across all customer workspaces'
                    : 'Performance snapshot for your latest flipbooks',
                'rows' => $catalogRows,
            ],
            'recentActivity' => $this->recentActivity($events, $books),
            'quickLinks' => $this->quickLinks($user),
        ];

        if ($user->isAdmin()) {
            return array_merge($shared, $this->adminPayload($summary, $viewTrend, $readingTrend, $books));
        }

        return array_merge($shared, $this->customerPayload($user, $summary, $viewTrend, $readingTrend, $clickTrend, $books));
    }

    private function adminPayload(array $summary, array $viewTrend, array $readingTrend, Collection $books): array
    {
        $totalRevenue = (int) BillingInvoice::query()->sum('amount_paid');
        $customersCount = User::query()->customers()->count();
        $activeCustomersCount = User::query()
            ->customers()
            ->where('last_login_at', '>=', now()->subDays(30))
            ->count();
        $openTicketsCount = SupportTicket::query()->where('status', '!=', 'closed')->count();

        $customerLeaderboard = $books
            ->groupBy(fn(array $book) => (int) data_get($book, 'owner.id'))
            ->map(function (Collection $ownerBooks) {
                $owner = data_get($ownerBooks->first(), 'owner');

                return [
                    'name' => (string) data_get($owner, 'name', 'Unknown customer'),
                    'email' => (string) data_get($owner, 'email', ''),
                    'catalogs' => $ownerBooks->count(),
                    'views' => (int) $ownerBooks->sum('views_count'),
                    'time_spent' => $this->formatDurationMs((int) $ownerBooks->sum('time_spent_ms')),
                ];
            })
            ->sortByDesc('views')
            ->take(5)
            ->values();

        return [
            'hero' => [
                'eyebrow' => 'Admin Workspace',
                'title' => 'Monitor catalog adoption, customers, and revenue from one place.',
                'description' => 'This dashboard aggregates flipbook engagement, active customers, billing totals, and support load so you can spot issues before they spread.',
            ],
            'cards' => [
                $this->card('Total Views', number_format((int) ($summary['views_count'] ?? 0)), $viewTrend['comparison'], number_format((int) ($summary['books_count'] ?? 0)) . ' tracked catalogs'),
                $this->card('Active Customers', number_format($customersCount), [
                    'label' => number_format($activeCustomersCount) . ' active in 30d',
                    'direction' => $activeCustomersCount > 0 ? 'up' : 'flat',
                ], 'Customers with admin-managed workspaces'),
                $this->card('Avg Engagement', $this->formatDurationMs($this->averageTimeSpentMs($summary)), $readingTrend['comparison'], 'Average reading time per book open'),
                $this->card('Revenue Collected', $this->formatCurrency($totalRevenue), [
                    'label' => number_format($openTicketsCount) . ' open tickets',
                    'direction' => $openTicketsCount > 0 ? 'down' : 'flat',
                ], 'Paid invoices synced from billing'),
            ],
            'secondaryPanel' => [
                'title' => 'Top Customer Workspaces',
                'subtitle' => 'Customers ranked by total flipbook views',
                'rows' => $customerLeaderboard,
            ],
        ];
    }

    private function customerPayload(User $user, array $summary, array $viewTrend, array $readingTrend, array $clickTrend, Collection $books): array
    {
        $plan = $this->billingManager->planFor($user);
        $usage = $this->billingManager->usageFor($user);
        $storageLimit = $this->billingManager->storageLimitBytes($plan);
        $analyticsEnabled = $this->billingManager->hasFeature($user, 'analytics');
        $avgDailyViews = (int) round(array_sum($viewTrend['points']) / max(count($viewTrend['points']), 1));

        return [
            'hero' => [
                'eyebrow' => 'Customer Workspace',
                'title' => 'Track how readers move through your flipbooks and what deserves attention next.',
                'description' => 'See your current catalog performance, reading depth, and plan usage in one dashboard without leaving the workspace.',
            ],
            'cards' => [
                $this->card('Total Views', number_format((int) ($summary['views_count'] ?? 0)), $viewTrend['comparison'], number_format((int) ($summary['books_count'] ?? 0)) . ' live catalogs'),
                $this->card('Average Daily Views', number_format($avgDailyViews), [
                    'label' => number_format((int) ($summary['readers_count'] ?? 0)) . ' unique readers',
                    'direction' => $avgDailyViews > 0 ? 'up' : 'flat',
                ], 'Average book opens over the last 7 days'),
                $this->card('Avg Engagement Time', $this->formatDurationMs($this->averageTimeSpentMs($summary)), $readingTrend['comparison'], 'Average reading time per book open'),
                $this->card('Avg Slices Clicked', number_format((int) ($summary['slice_click_count'] ?? 0)), $clickTrend['comparison'], 'Total hotspot interactions captured'),
            ],
            'secondaryPanel' => [
                'title' => 'Plan & Usage',
                'subtitle' => 'Current billing limits and feature access',
                'plan_name' => $plan->name,
                'plan_description' => $plan->description,
                'analytics_enabled' => $analyticsEnabled,
                'storage' => $this->billingManager->formatBytes((int) ($usage['storage_bytes'] ?? 0)) . ' / ' . $this->billingManager->formatBytes($storageLimit),
                'catalogs' => number_format((int) ($usage['flipbooks_count'] ?? 0)),
                'billing_url' => route('billing.index'),
                'analytics_url' => $analyticsEnabled ? route('analytics.index') : route('billing.index'),
                'analytics_cta' => $analyticsEnabled ? 'Open Analytics' : 'Upgrade For Analytics',
            ],
        ];
    }

    private function quickLinks(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                ['label' => 'Manage customers', 'url' => route('admin.customers.index')],
                ['label' => 'Review analytics', 'url' => route('analytics.index')],
                ['label' => 'Open billing', 'url' => route('admin.billing.index')],
            ];
        }

        return [
            ['label' => 'Upload catalog', 'url' => route('catalog.pdfs.create')],
            ['label' => 'View catalogs', 'url' => route('catalog.pdfs.index')],
            ['label' => 'Billing & plans', 'url' => route('billing.index')],
        ];
    }

    private function recentActivity(Collection $events, Collection $books): Collection
    {
        $titles = $books
            ->mapWithKeys(fn(array $book) => [(int) data_get($book, 'pdf.id') => (string) data_get($book, 'pdf.title', 'Untitled catalog')]);

        return $events
            ->sortByDesc('created_at')
            ->take(6)
            ->map(function (CatalogPdfEvent $event) use ($titles) {
                $catalogTitle = $titles->get($event->catalog_pdf_id, 'Untitled catalog');

                return [
                    'title' => $catalogTitle,
                    'description' => $this->eventDescription($event),
                    'timestamp' => optional($event->created_at)->diffForHumans() ?: 'just now',
                ];
            })
            ->values();
    }

    private function eventDescription(CatalogPdfEvent $event): string
    {
        return match ($event->event_type) {
            CatalogPdfEvent::EVENT_BOOK_OPEN => 'A reader opened the flipbook.',
            CatalogPdfEvent::EVENT_PAGE_VIEW => 'A reader moved to page ' . ((int) $event->page_number ?: 1) . '.',
            CatalogPdfEvent::EVENT_READING_TIME => 'Reading time was captured for this session.',
            CatalogPdfEvent::EVENT_HOTSPOT_CLICK => 'A hotspot interaction was recorded.',
            default => 'New reader activity was recorded.',
        };
    }

    private function trend(Collection $events, string $eventType, int $days = 7, ?callable $valueResolver = null): array
    {
        $today = now()->startOfDay();
        $labels = [];
        $points = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = $today->copy()->subDays($offset);
            $nextDay = $day->copy()->addDay();
            $dayEvents = $events
                ->where('event_type', $eventType)
                ->filter(fn(CatalogPdfEvent $event) => $event->created_at && $event->created_at->between($day, $nextDay, false));

            $labels[] = $day->format('M d');
            $points[] = $valueResolver
                ? (int) $dayEvents->sum(fn(CatalogPdfEvent $event) => $valueResolver($event))
                : $dayEvents->count();
        }

        $currentStart = $today->copy()->subDays($days - 1);
        $previousStart = $today->copy()->subDays(($days * 2) - 1);
        $previousEnd = $today->copy()->subDays($days);

        $currentValue = $this->trendValue($events, $eventType, $currentStart, $today->copy()->addDay(), $valueResolver);
        $previousValue = $this->trendValue($events, $eventType, $previousStart, $previousEnd->copy()->addDay(), $valueResolver);

        return [
            'labels' => $labels,
            'points' => $points,
            'comparison' => $this->comparison($currentValue, $previousValue),
        ];
    }

    private function trendValue(Collection $events, string $eventType, Carbon $start, Carbon $end, ?callable $valueResolver = null): int
    {
        $matching = $events
            ->where('event_type', $eventType)
            ->filter(fn(CatalogPdfEvent $event) => $event->created_at && $event->created_at->between($start, $end, false));

        if ($valueResolver) {
            return (int) $matching->sum(fn(CatalogPdfEvent $event) => $valueResolver($event));
        }

        return $matching->count();
    }

    private function completionRate(Collection $events): int
    {
        $pageViews = $events->where('event_type', CatalogPdfEvent::EVENT_PAGE_VIEW);
        $maxPage = (int) $pageViews->max('page_number');

        if ($maxPage < 1) {
            return 0;
        }

        $readerKeys = $this->readerKeys($pageViews);
        if ($readerKeys->isEmpty()) {
            return 0;
        }

        $finisherKeys = $this->readerKeys(
            $pageViews->filter(fn(CatalogPdfEvent $event) => (int) $event->page_number === $maxPage)
        );

        return (int) round(($finisherKeys->count() / max($readerKeys->count(), 1)) * 100);
    }

    private function readerKeys(Collection $events): Collection
    {
        return $events
            ->map(function (CatalogPdfEvent $event) {
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
            })
            ->filter()
            ->unique()
            ->values();
    }

    private function comparison(int $current, int $previous): array
    {
        if ($current === 0 && $previous === 0) {
            return ['label' => '0%', 'direction' => 'flat'];
        }

        if ($previous === 0) {
            return ['label' => '+100%', 'direction' => 'up'];
        }

        $percent = (($current - $previous) / $previous) * 100;

        if (abs($percent) < 0.05) {
            return ['label' => '0%', 'direction' => 'flat'];
        }

        return [
            'label' => ($percent > 0 ? '+' : '') . number_format($percent, 1) . '%',
            'direction' => $percent > 0 ? 'up' : 'down',
        ];
    }

    private function card(string $label, string $value, array $comparison, string $hint): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'comparison' => $comparison,
            'hint' => $hint,
        ];
    }

    private function averageTimeSpentMs(array $summary): int
    {
        $viewsCount = max(1, (int) ($summary['views_count'] ?? 0));

        return (int) floor(((int) ($summary['time_spent_ms'] ?? 0)) / $viewsCount);
    }

    private function formatCurrency(int $amountCents, string $currency = 'USD'): string
    {
        return strtoupper($currency) . ' ' . number_format($amountCents / 100, 2);
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

        return implode(' ', array_slice($parts, 0, 2));
    }
}
