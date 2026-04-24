<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\CatalogPdfAnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
        ];

        $customerQuery = User::query()
            ->customers()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']) . '%';

                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            });

        $customers = (clone $customerQuery)
            ->withCount(['catalogPdfs', 'supportTickets', 'billingInvoices'])
            ->withMax('catalogPdfs', 'created_at')
            ->orderBy('name')
            ->orderBy('email')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'customers_count' => (clone $customerQuery)->count(),
            'verified_count' => (clone $customerQuery)->whereNotNull('email_verified_at')->count(),
            'active_count' => (clone $customerQuery)->where('last_login_at', '>=', now()->subDays(30))->count(),
            'catalogs_count' => CatalogPdf::query()
                ->whereIn('user_id', (clone $customerQuery)->select('id'))
                ->count(),
        ];

        return view('pages.apps.customers.index', [
            'customers' => $customers,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, User $customer, CatalogPdfAnalyticsService $analyticsService)
    {
        $customer = User::query()
            ->customers()
            ->with(['addresses', 'roles'])
            ->findOrFail($customer->id);

        $catalogPdfs = $customer->catalogPdfs()
            ->latest()
            ->get();

        $catalogPdfIds = $catalogPdfs->pluck('id');

        $analytics = $analyticsService->forViewer($request->user(), $customer);

        $ticketQuery = SupportTicket::query()->with('categoryRelation')->where('user_id', $customer->id);
        $invoiceQuery = BillingInvoice::query()->where('user_id', $customer->id);
        $transactionQuery = BillingTransaction::query()->with('invoice')->where('user_id', $customer->id);

        $supportTickets = (clone $ticketQuery)
            ->latest()
            ->limit(10)
            ->get();

        $invoices = (clone $invoiceQuery)
            ->latest()
            ->limit(10)
            ->get();

        $transactions = (clone $transactionQuery)
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $activityLog = $this->activityLog($customer, $catalogPdfIds);

        return view('pages.apps.customers.show', [
            'customer' => $customer,
            'catalogPdfs' => $catalogPdfs,
            'analyticsSummary' => $analytics['summary'],
            'analyticsBooks' => $analytics['books'],
            'catalogSummary' => [
                'uploaded_count' => $catalogPdfs->count(),
                'public_count' => $catalogPdfs->where('visibility', CatalogPdf::VISIBILITY_PUBLIC)->count(),
                'private_count' => $catalogPdfs->where('visibility', CatalogPdf::VISIBILITY_PRIVATE)->count(),
                'storage_human' => $this->humanReadableBytes((int) $catalogPdfs->sum('size')),
            ],
            'supportSummary' => [
                'total' => (clone $ticketQuery)->count(),
                'open' => (clone $ticketQuery)->where('status', '!=', 'closed')->count(),
                'closed' => (clone $ticketQuery)->where('status', 'closed')->count(),
            ],
            'billingSummary' => [
                'invoices_count' => (clone $invoiceQuery)->count(),
                'amount_paid' => (int) (clone $invoiceQuery)->sum('amount_paid'),
                'transactions_count' => (clone $transactionQuery)->count(),
                'successful_transactions' => (clone $transactionQuery)->where('status', 'succeeded')->count(),
            ],
            'supportTickets' => $supportTickets,
            'invoices' => $invoices,
            'transactions' => $transactions,
            'activityLog' => $activityLog,
        ]);
    }

    private function activityLog(User $customer, Collection $catalogPdfIds): Collection
    {
        $activity = collect();

        if ($customer->last_login_at) {
            $activity->push([
                'badge_class' => 'badge-light-primary',
                'headline' => 'Customer signed in',
                'details' => $customer->last_login_ip ? 'IP ' . $customer->last_login_ip : 'Last known account sign-in.',
                'context' => 'Account access',
                'timestamp' => $customer->last_login_at,
            ]);
        }

        if ($catalogPdfIds->isEmpty()) {
            return $activity->sortByDesc('timestamp')->values();
        }

        $events = CatalogPdfEvent::query()
            ->whereIn('catalog_pdf_id', $catalogPdfIds)
            ->with(['pdf:id,title', 'hotspot:id,title'])
            ->latest('created_at')
            ->limit(15)
            ->get();

        $activity = $activity->merge($events->map(function (CatalogPdfEvent $event) {
            return [
                'badge_class' => $this->eventBadgeClass($event),
                'headline' => $this->eventHeadline($event),
                'details' => $this->eventDetails($event),
                'context' => $event->pdf?->title ?? 'Catalog PDF',
                'timestamp' => $event->created_at,
            ];
        }));

        return $activity->sortByDesc('timestamp')->values();
    }

    private function eventBadgeClass(CatalogPdfEvent $event): string
    {
        return match ($event->event_type) {
            CatalogPdfEvent::EVENT_BOOK_OPEN => 'badge-light-primary',
            CatalogPdfEvent::EVENT_PAGE_VIEW => 'badge-light-info',
            CatalogPdfEvent::EVENT_READING_TIME => 'badge-light-success',
            CatalogPdfEvent::EVENT_HOTSPOT_CLICK => 'badge-light-warning',
            default => 'badge-light',
        };
    }

    private function eventHeadline(CatalogPdfEvent $event): string
    {
        return match ($event->event_type) {
            CatalogPdfEvent::EVENT_BOOK_OPEN => 'Viewer opened the catalog',
            CatalogPdfEvent::EVENT_PAGE_VIEW => 'Viewer opened a page',
            CatalogPdfEvent::EVENT_READING_TIME => 'Reading time recorded',
            CatalogPdfEvent::EVENT_HOTSPOT_CLICK => 'Hotspot interaction captured',
            default => 'Catalog activity captured',
        };
    }

    private function eventDetails(CatalogPdfEvent $event): string
    {
        return match ($event->event_type) {
            CatalogPdfEvent::EVENT_PAGE_VIEW => 'Page ' . ($event->page_number ?: 'n/a') . ' was viewed.',
            CatalogPdfEvent::EVENT_READING_TIME => 'Tracked ' . $this->formatDurationMs((int) data_get($event->meta, 'duration_ms', 0)) . ' of reading time.',
            CatalogPdfEvent::EVENT_HOTSPOT_CLICK => 'Hotspot ' . ($event->hotspot?->title ?: 'interaction') . ' was clicked.',
            default => $event->session_id ? 'Session ' . $event->session_id : 'Viewer activity recorded.',
        };
    }

    private function formatDurationMs(int $milliseconds): string
    {
        $seconds = max(0, (int) floor($milliseconds / 1000));
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes <= 0) {
            return $remainingSeconds . 's';
        }

        if ($remainingSeconds === 0) {
            return $minutes . 'm';
        }

        return $minutes . 'm ' . $remainingSeconds . 's';
    }

    private function humanReadableBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = $bytes / 1024;

        foreach ($units as $unit) {
            if ($size < 1024 || $unit === end($units)) {
                return number_format($size, $size >= 10 ? 0 : 1) . ' ' . $unit;
            }

            $size /= 1024;
        }

        return number_format($size, 1) . ' TB';
    }
}
