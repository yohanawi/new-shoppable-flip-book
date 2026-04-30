<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CatalogPdf;
use App\Models\CatalogPdfEvent;
use App\Models\CatalogPdfHotspot;
use App\Models\User;
use App\Services\BillingManager;
use App\Services\CatalogPdfAnalyticsService;
use App\Services\Notifications\CatalogPdfMilestoneNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CatalogAnalyticsController extends Controller
{
    public function index(Request $request, CatalogPdfAnalyticsService $analyticsService, BillingManager $billingManager)
    {
        $user = $request->user();

        abort_unless($user, 403);

        $selectedOwner = null;

        if ($user->isAdmin()) {
            $selectedOwner = $this->selectedOwner($request);
        } else {
            abort_unless($user->isCustomer(), 403);
            abort_unless($user->can('customer.analytics.view'), 403);
            abort_unless(
                $billingManager->hasFeature($user, 'analytics'),
                403,
                'Your current billing plan does not include this feature.'
            );
        }

        $analytics = $analyticsService->forViewer($user, $selectedOwner);

        $ownerOptions = $user->isAdmin()
            ? User::query()
            ->whereHas('catalogPdfs')
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email'])
            : collect();

        return view('pages.apps.analytics.index', array_merge($analytics, [
            'isAdminView' => $user->isAdmin(),
            'ownerOptions' => $ownerOptions,
            'selectedOwner' => $selectedOwner,
        ]));
    }

    public function track(Request $request, CatalogPdf $catalogPdf, CatalogPdfMilestoneNotificationService $milestoneNotificationService)
    {
        $this->authorizeViewer($catalogPdf);

        $validated = $request->validate([
            'event_type' => ['required', 'string', Rule::in(CatalogPdfEvent::trackedEventTypes())],
            'page_number' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'hotspot_id' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
        ]);

        $hotspotId = $validated['hotspot_id'] ?? null;
        if ($hotspotId) {
            $hotspotId = CatalogPdfHotspot::query()
                ->where('id', $hotspotId)
                ->where('catalog_pdf_id', $catalogPdf->id)
                ->value('id');
        }

        $meta = is_array($validated['meta'] ?? null) ? $validated['meta'] : [];
        if (isset($meta['duration_ms'])) {
            $meta['duration_ms'] = max(0, min((int) $meta['duration_ms'], 600000));
        }

        $event = CatalogPdfEvent::create([
            'catalog_pdf_id' => $catalogPdf->id,
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'event_type' => $validated['event_type'],
            'page_number' => $validated['page_number'] ?? null,
            'catalog_pdf_hotspot_id' => $hotspotId,
            'meta' => $meta !== [] ? $meta : null,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'created_at' => now(),
        ]);

        $milestoneNotificationService->handleTrackedEvent($catalogPdf, $event->event_type);

        return response()->json(['ok' => true]);
    }

    private function authorizeViewer(CatalogPdf $catalogPdf): void
    {
        if (Auth::user()?->isAdmin()) {
            return;
        }

        if ($catalogPdf->visibility === CatalogPdf::VISIBILITY_PRIVATE && $catalogPdf->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function selectedOwner(Request $request): ?User
    {
        $ownerId = $request->integer('owner');

        if (!$ownerId) {
            return null;
        }

        return User::query()->findOrFail($ownerId);
    }
}
