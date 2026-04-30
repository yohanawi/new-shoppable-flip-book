<x-default-layout>

    @section('title')
        Billing Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('admin.billing.index') }}
    @endsection

    @php
        $notificationUser = auth()->user();
        $canViewNotifications = $notificationUser?->can('notifications.view') ?? false;
        $canManageNotifications = $notificationUser?->can('notifications.manage') ?? false;
        $unreadCount = $canViewNotifications ? $notificationUser?->unreadNotifications()->count() ?? 0 : 0;
        $recentNotifications = $canViewNotifications
            ? $notificationUser->notifications()->latest()->limit(4)->get()
            : collect();
        $realtimeEnabled =
            $canViewNotifications && config('broadcasting.default') === 'pusher' && filled(env('PUSHER_APP_KEY'));
        $createForm = [
            'name' => old('name', ''),
            'slug' => old('slug', ''),
            'description' => old('description', ''),
            'price' => old('price', '0'),
            'currency' => old('currency', 'usd'),
            'interval' => old('interval', 'month'),
            'trial_days' => old('trial_days', ''),
            'flipbooks' => old('limits.flipbooks', ''),
            'storage_mb' => old('limits.storage_mb', ''),
            'stripe_price_id' => old('stripe_price_id', ''),
            'stripe_product_id' => old('stripe_product_id', ''),
            'sort_order' => old('sort_order', '0'),
            'analytics' => old('features.analytics'),
            'branding' => old('features.branding'),
            'is_active' => old('is_active', '1'),
        ];
    @endphp

    <style>
        .billing-admin-shell .billing-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 48%, #38bdf8 100%);
            border: 0;
        }

        .billing-admin-shell .billing-hero::before,
        .billing-admin-shell .billing-hero::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
        }

        .billing-admin-shell .billing-hero::before {
            width: 18rem;
            height: 18rem;
            top: -8rem;
            right: -4rem;
        }

        .billing-admin-shell .billing-hero::after {
            width: 12rem;
            height: 12rem;
            bottom: -5rem;
            left: -2rem;
        }

        .billing-admin-shell .billing-metric {
            position: relative;
            z-index: 1;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
        }

        .billing-admin-shell .billing-panel,
        .billing-admin-shell .billing-table-card {
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
        }

        .billing-admin-shell .billing-plan-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .billing-admin-shell .billing-plan-row:hover {
            background-color: var(--bs-gray-100);
        }

        .billing-admin-shell .billing-alert-item {
            border-radius: 1rem;
            border: 1px dashed var(--bs-border-color);
        }

        .billing-admin-shell .billing-notification-timeline {
            max-height: 24rem;
            overflow-y: auto;
        }

        .billing-admin-shell .billing-section-anchor {
            scroll-margin-top: 8rem;
        }
    </style>

    <div class="billing-admin-shell">
        @if ($errors->any())
            <div class="alert alert-danger d-flex align-items-start mb-8">
                <span class="svg-icon svg-icon-2hx svg-icon-danger me-4 mt-1">{!! getIcon('cross-circle', 'fs-2hx text-danger') !!}</span>
                <div>
                    <div class="fw-bold mb-2">Admin billing action could not be completed.</div>
                    <ul class="mb-0 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="row g-6 mb-8 align-items-stretch">
            <div class="col-xxl-12">
                <div class="card billing-hero h-100">
                    <div class="card-body p-4 position-relative">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-10 position-relative">
                            <div class="row g-4 w-100">
                                <div class="col-sm-3">
                                    <div class="billing-metric p-6 h-100">
                                        <div class="text-white opacity-75 fw-semibold fs-7 mb-2">30 Day Revenue</div>
                                        <div class="text-white fs-2hx fw-bolder">USD
                                            {{ number_format($metrics['monthly_revenue'] / 100, 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="billing-metric p-6 h-100">
                                        <div class="text-white opacity-75 fw-semibold fs-7 mb-2">Active Subscriptions
                                        </div>
                                        <div class="text-white fs-2hx fw-bolder">
                                            {{ number_format($metrics['active_subscriptions']) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="billing-metric p-6 h-100">
                                        <div class="text-white opacity-75 fw-semibold fs-7 mb-2">Churned in 30 Days
                                        </div>
                                        <div class="text-white fs-2hx fw-bolder">
                                            {{ number_format($metrics['churned_subscriptions']) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="billing-metric p-6 h-100">
                                        <div class="text-white opacity-75 fw-semibold fs-7 mb-2">Failed Payments</div>
                                        <div class="text-white fs-2hx fw-bolder">
                                            {{ number_format($metrics['failed_payments']) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-12">
                <div class="card billing-panel h-100 billing-section-anchor" id="billing-payment-requests">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <span class="fs-2 fw-bold text-gray-900">Manual Payment Requests</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Approve, reject, and track customer
                                payment proofs submitted for manual review.</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if ($paymentRequests->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle gs-0 gy-4">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th>Request</th>
                                            <th>Customer</th>
                                            <th>Plan</th>
                                            <th>Source</th>
                                            <th>Status</th>
                                            <th>Reference</th>
                                            <th>Receipt</th>
                                            <th>Review</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentRequests as $paymentRequest)
                                            @php
                                                $requestTone = match ($paymentRequest->status) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'under_review' => 'warning',
                                                    default => 'primary',
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-900">{{ $paymentRequest->requestNumber() }}</span>
                                                        <span
                                                            class="text-muted fs-7">{{ optional($paymentRequest->submitted_at ?? $paymentRequest->created_at)->format('d M Y H:i') }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-900">{{ $paymentRequest->user?->name ?? 'Unknown customer' }}</span>
                                                        <span
                                                            class="text-muted fs-7">{{ $paymentRequest->user?->email }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $paymentRequest->plan?->name ?? 'Unknown plan' }}</td>
                                                <td>{{ $paymentRequest->gatewayLabel() }}</td>
                                                <td><span
                                                        class="badge badge-light-{{ $requestTone }}">{{ $paymentRequest->statusLabel() }}</span>
                                                </td>
                                                <td>{{ $paymentRequest->transaction_reference ?: 'N/A' }}</td>
                                                <td>
                                                    @if ($paymentRequest->receiptUrl())
                                                        <a href="{{ $paymentRequest->receiptUrl() }}" target="_blank"
                                                            class="btn btn-sm btn-light-primary">Open</a>
                                                    @else
                                                        <span class="text-muted fs-7">Missing</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($paymentRequest->status === 'approved')
                                                        <div class="text-muted fs-7">Approved by
                                                            {{ $paymentRequest->reviewer?->name ?? 'billing team' }}
                                                        </div>
                                                    @else
                                                        <form method="POST"
                                                            action="{{ route('admin.billing.payment-requests.review', $paymentRequest) }}">
                                                            @csrf
                                                            <div class="mb-3">
                                                                <textarea name="admin_note" rows="3" class="form-control form-control-solid form-control-sm"
                                                                    placeholder="Add a review note (required for rejection).">{{ old('admin_note') }}</textarea>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" name="review_action"
                                                                    value="approve"
                                                                    class="btn btn-sm btn-light-success">Approve</button>
                                                                <button type="submit" name="review_action"
                                                                    value="reject"
                                                                    class="btn btn-sm btn-light-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted py-8">No manual payment requests have been submitted yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- <div class="col-xxl-4">
                <div class="card billing-panel h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <span class="fs-2 fw-bold text-gray-900">Billing Notifications</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Operational alerts and recent inbox
                                activity.</span>
                        </div>
                        @if ($canViewNotifications)
                            <div class="card-toolbar">
                                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">Open
                                    Inbox</a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body pt-0">
                        <div class="rounded-4 p-6 mb-6"
                            style="background: linear-gradient(135deg, #111827 0%, #0f766e 100%);">
                            <div class="d-flex justify-content-between align-items-start gap-4">
                                <div>
                                    <div class="text-white fw-bold fs-3 mb-1">{{ number_format($unreadCount) }} unread
                                    </div>
                                    <div class="text-white opacity-75 fs-7">
                                        @if ($canViewNotifications)
                                            {{ $realtimeEnabled ? 'Realtime delivery is enabled through the global inbox bell.' : 'Inbox access is enabled, but realtime delivery is currently idle.' }}
                                        @else
                                            Notification inbox access is not available for this account.
                                        @endif
                                    </div>
                                </div>
                                <span
                                    class="badge badge-light-success">{{ $canManageNotifications ? 'Managed feed' : 'Read only' }}</span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fs-5 fw-bold text-gray-900">System Alerts</div>
                                <a href="#billing-plan-management" class="fs-7 fw-semibold text-primary">Go to billing
                                    tools</a>
                            </div>

                            @forelse ($billingNotifications as $billingNotification)
                                <div class="billing-alert-item p-4 mb-3 bg-light-{{ $billingNotification['tone'] }}">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="svg-icon svg-icon-2 text-{{ $billingNotification['tone'] }} mt-1">
                                            {!! getIcon($billingNotification['icon'], 'fs-2 text-' . $billingNotification['tone']) !!}
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-gray-900 mb-1">{{ $billingNotification['title'] }}
                                            </div>
                                            <div class="text-gray-700 fs-7 mb-2">{{ $billingNotification['message'] }}
                                            </div>
                                            <a href="{{ $billingNotification['anchor'] }}"
                                                class="fs-7 fw-semibold text-{{ $billingNotification['tone'] }}">
                                                {{ $billingNotification['action'] }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="billing-alert-item p-4 bg-light-success">
                                    <div class="fw-bold text-success mb-1">All core billing signals look healthy.</div>
                                    <div class="text-gray-700 fs-7">No urgent payment failures, broken plan mappings, or
                                        subscription exceptions were detected in the current snapshot.</div>
                                </div>
                            @endforelse
                        </div>

                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fs-5 fw-bold text-gray-900">Recent Inbox Activity</div>
                                @if ($canViewNotifications && $canManageNotifications && $unreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light">Mark all read</button>
                                    </form>
                                @endif
                            </div>

                            @if ($canViewNotifications)
                                <div class="billing-notification-timeline pe-2">
                                    @forelse ($recentNotifications as $notification)
                                        @php
                                            $notificationTitle = data_get(
                                                $notification->data,
                                                'title',
                                                class_basename($notification->type),
                                            );
                                            $notificationMessage = data_get(
                                                $notification->data,
                                                'message',
                                                'Notification received.',
                                            );
                                            $notificationActionUrl = data_get($notification->data, 'action_url');
                                        @endphp
                                        <div class="d-flex gap-4 py-4 border-bottom border-gray-100">
                                            <div class="symbol symbol-40px">
                                                <span
                                                    class="symbol-label {{ $notification->read_at ? 'bg-light' : 'bg-light-primary' }}">
                                                    <i
                                                        class="ki-outline ki-notification-bing fs-2 {{ $notification->read_at ? 'text-gray-500' : 'text-primary' }}"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                    <span class="fw-bold text-gray-900">{{ $notificationTitle }}</span>
                                                    @if (blank($notification->read_at))
                                                        <span class="badge badge-light-danger">New</span>
                                                    @endif
                                                </div>
                                                <div class="text-gray-600 fs-7 mb-2">{{ $notificationMessage }}</div>
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <span
                                                        class="badge badge-light fs-8">{{ $notification->created_at?->diffForHumans() }}</span>
                                                    @if ($notificationActionUrl)
                                                        <a href="{{ $notificationActionUrl }}"
                                                            class="fs-7 fw-semibold text-primary">Open</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-10 text-gray-500 fs-7">
                                            No recent inbox items yet.
                                        </div>
                                    @endforelse
                                </div>
                            @else
                                <div class="alert alert-light-warning mb-0">
                                    Notification inbox access is restricted for this account. Billing alerts above
                                    remain available on this dashboard.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <div class="card billing-table-card mb-8 billing-section-anchor" id="billing-plan-management">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <span class="fs-2 fw-bold text-gray-900">Plan Management</span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Metronic table for creating, editing, and safely
                        deleting plans.</span>
                </div>
                <div class="card-toolbar flex-wrap gap-3">
                    <div class="d-flex align-items-center position-relative my-1">
                        {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                        <input type="text" id="billing-plan-search"
                            class="form-control form-control-solid w-250px ps-13" placeholder="Search plans">
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_create_plan">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Create Plan
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-275px">Plan</th>
                                <th class="min-w-180px">Pricing</th>
                                <th class="min-w-220px">Limits &amp; Features</th>
                                <th class="min-w-220px">Stripe Mapping</th>
                                <th class="min-w-160px">Status</th>
                                <th class="text-end min-w-150px">Action</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($plans as $plan)
                                <tr class="billing-plan-row" data-plan-row
                                    data-plan-search="{{ strtolower(trim($plan->name . ' ' . $plan->slug . ' ' . ($plan->description ?? ''))) }}">
                                    <td>
                                        <div class="d-flex align-items-start gap-4">
                                            <div class="symbol symbol-50px">
                                                <span class="symbol-label bg-light-primary text-primary fw-bolder fs-3">
                                                    {{ strtoupper(substr($plan->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                    <span
                                                        class="text-gray-900 fw-bold fs-6">{{ $plan->name }}</span>
                                                    <span
                                                        class="badge badge-light-{{ $plan->isFree() ? 'success' : 'primary' }}">
                                                        {{ $plan->isFree() ? 'Free' : 'Paid' }}
                                                    </span>
                                                    @if (!$plan->is_active)
                                                        <span class="badge badge-light-danger">Paused</span>
                                                    @endif
                                                </div>
                                                <div class="text-gray-500 fs-7 mb-2">/{{ $plan->slug }} •
                                                    {{ number_format($plan->subscriptions_count) }} subscribers</div>
                                                <div class="text-gray-700 fs-7">
                                                    {{ \Illuminate\Support\Str::limit($plan->description ?: 'No description added yet.', 105) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-gray-900 fw-bold mb-1">{{ $plan->formattedPrice() }}</div>
                                        <div class="text-gray-500 fs-7 text-uppercase">{{ $plan->interval }}</div>
                                        <div class="text-gray-500 fs-7 mt-2">Trial: {{ $plan->trial_days ?: 0 }} days
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span
                                                class="badge badge-light-info">{{ data_get($plan->limits, 'flipbooks') ?: 'Unlimited' }}
                                                flipbooks</span>
                                            <span
                                                class="badge badge-light-warning">{{ data_get($plan->limits, 'storage_mb') ?: 'Unlimited' }}
                                                MB</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span
                                                class="badge badge-light-{{ $plan->hasFeature('analytics') ? 'success' : 'secondary' }}">Analytics
                                                {{ $plan->hasFeature('analytics') ? 'On' : 'Off' }}</span>
                                            <span
                                                class="badge badge-light-{{ $plan->hasFeature('branding') ? 'success' : 'secondary' }}">Branding
                                                {{ $plan->hasFeature('branding') ? 'On' : 'Off' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-2">
                                            <div class="text-gray-900 fw-bold fs-7">Price ID</div>
                                            <div class="text-gray-600 fs-7">
                                                {{ $plan->stripe_price_id ?: 'Not connected' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-900 fw-bold fs-7">Product ID</div>
                                            <div class="text-gray-600 fs-7">
                                                {{ $plan->stripe_product_id ?: 'Not connected' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <span
                                                class="badge badge-light-{{ $plan->is_active ? 'success' : 'danger' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                                            <span class="badge badge-light">Sort #{{ $plan->sort_order }}</span>
                                            @if (!$plan->isFree() && blank($plan->stripe_price_id))
                                                <span class="badge badge-light-warning">Needs Stripe mapping</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-light-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#kt_modal_edit_plan_{{ $plan->id }}">
                                                Edit
                                            </button>

                                            <form method="POST"
                                                action="{{ route('admin.billing.plans.destroy', $plan) }}"
                                                data-swal-confirm data-swal-title="Delete {{ $plan->name }}?"
                                                data-swal-text="This plan record will be permanently removed from the billing catalog."
                                                data-swal-confirm-text="Delete plan"
                                                data-swal-cancel-text="Keep plan">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-light-{{ $plan->subscriptions_count ? 'secondary' : 'danger' }}"
                                                    {{ $plan->subscriptions_count ? 'disabled' : '' }}>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                        @if ($plan->subscriptions_count)
                                            <div class="text-gray-500 fs-8 mt-2">Delete is locked while subscription
                                                history exists.</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-10">No plans have been
                                        configured yet.</td>
                                </tr>
                            @endforelse
                            <tr id="billing-plan-empty" class="d-none">
                                <td colspan="6" class="text-center text-muted py-10">No plans match the current
                                    search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card billing-table-card mb-8 billing-section-anchor" id="billing-subscriptions">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column">
                    <span class="fs-2 fw-bold text-gray-900">Subscription Management</span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-7">Swap customers between plans or schedule
                        cancellations with confirmation safeguards.</span>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Customer</th>
                                <th>Current Plan</th>
                                <th>Status</th>
                                <th>Ends</th>
                                <th class="text-end min-w-325px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($subscriptions as $subscription)
                                @php
                                    $subscriptionTone = in_array(
                                        $subscription->stripe_status,
                                        ['active', 'trialing'],
                                        true,
                                    )
                                        ? 'success'
                                        : ($subscription->stripe_status === 'past_due'
                                            ? 'warning'
                                            : 'secondary');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span
                                                class="text-gray-900 fw-bold">{{ $subscription->owner?->name ?? 'Unknown user' }}</span>
                                            <span class="text-muted fs-7">{{ $subscription->owner?->email }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-gray-900 fw-bold">
                                            {{ $subscription->plan?->name ?? 'Unmapped Stripe plan' }}</div>
                                        <div class="text-gray-500 fs-7">Stripe price:
                                            {{ $subscription->stripe_price }}</div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-light-{{ $subscriptionTone }}">{{ ucfirst(str_replace('_', ' ', $subscription->stripe_status)) }}</span>
                                    </td>
                                    <td>{{ optional($subscription->ends_at)->format('d M Y') ?? 'Active' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <form method="POST"
                                                action="{{ route('admin.billing.subscriptions.swap', $subscription) }}"
                                                class="d-flex gap-2 flex-wrap justify-content-end" data-swal-confirm
                                                data-swal-title="Swap subscription plan?"
                                                data-swal-text="The selected customer will be moved to the chosen plan in Stripe and locally."
                                                data-swal-confirm-text="Apply plan change"
                                                data-swal-cancel-text="Keep current plan">
                                                @csrf
                                                <select name="plan_id"
                                                    class="form-select form-select-solid form-select-sm w-175px">
                                                    @foreach ($plans as $plan)
                                                        <option value="{{ $plan->id }}"
                                                            {{ (int) $subscription->plan_id === (int) $plan->id ? 'selected' : '' }}>
                                                            {{ $plan->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                    class="btn btn-sm btn-light-primary">Swap</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('admin.billing.subscriptions.cancel', $subscription) }}"
                                                data-swal-confirm data-swal-title="Cancel subscription?"
                                                data-swal-text="This schedules cancellation for the customer's Stripe subscription."
                                                data-swal-confirm-text="Schedule cancellation"
                                                data-swal-cancel-text="Keep subscription">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm btn-light-danger">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-10">No Stripe subscriptions
                                        have been synced yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-6 g-xl-9">
            <div class="col-xl-6 billing-section-anchor" id="billing-invoices">
                <div class="card billing-table-card h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <span class="fs-2 fw-bold text-gray-900">Recent Invoices</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Latest billing outcomes and downloadable
                                invoice files.</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th class="text-end">PDF</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @forelse ($invoices as $invoice)
                                        @php
                                            $invoiceTone =
                                                $invoice->status === 'paid'
                                                    ? 'success'
                                                    : (in_array($invoice->status, ['failed', 'uncollectible'], true)
                                                        ? 'danger'
                                                        : 'warning');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="text-gray-900 fw-bold">{{ $invoice->user?->email ?? 'Unknown user' }}</span>
                                                    <span
                                                        class="text-muted fs-7">{{ $invoice->created_at?->format('d M Y H:i') }}</span>
                                                </div>
                                            </td>
                                            <td><span
                                                    class="badge badge-light-{{ $invoiceTone }}">{{ ucfirst($invoice->status) }}</span>
                                            </td>
                                            <td>{{ strtoupper($invoice->currency) }}
                                                {{ number_format(($invoice->amount_paid ?: $invoice->amount_due) / 100, 2) }}
                                            </td>
                                            <td class="text-end">
                                                @if ($invoice->invoice_pdf_url)
                                                    <a href="{{ $invoice->invoice_pdf_url }}" target="_blank"
                                                        class="btn btn-sm btn-light-primary">Open</a>
                                                @else
                                                    <span class="text-muted fs-7">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-10">No invoice records
                                                yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card billing-table-card h-100">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title flex-column">
                            <span class="fs-2 fw-bold text-gray-900">Recent Transactions</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Card charges, refunds, and payment events
                                flowing through the billing stack.</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @forelse ($transactions as $transaction)
                                        @php
                                            $transactionTone =
                                                $transaction->status === 'succeeded'
                                                    ? 'success'
                                                    : ($transaction->status === 'failed'
                                                        ? 'danger'
                                                        : 'warning');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="text-gray-900 fw-bold">{{ $transaction->user?->email ?? 'Unknown user' }}</span>
                                                    <span
                                                        class="text-muted fs-7">{{ optional($transaction->processed_at ?? $transaction->created_at)->format('d M Y H:i') }}</span>
                                                </div>
                                            </td>
                                            <td>{{ ucfirst($transaction->type) }}</td>
                                            <td><span
                                                    class="badge badge-light-{{ $transactionTone }}">{{ ucfirst($transaction->status) }}</span>
                                            </td>
                                            <td>{{ strtoupper($transaction->currency) }}
                                                {{ number_format($transaction->amount / 100, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-10">No payment records
                                                yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_create_plan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.billing.plans.store') }}">
                    @csrf
                    <input type="hidden" name="billing_form" value="create">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h2 class="fw-bold mb-1">Create Plan</h2>
                            <div class="text-muted fs-7">Create a new billing offer with limits, features, and Stripe
                                references.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary"
                            data-bs-dismiss="modal">
                            {!! getIcon('cross', 'fs-1') !!}
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-12">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <label class="form-label required">Name</label>
                                <input type="text" name="name" class="form-control form-control-solid"
                                    value="{{ $createForm['name'] }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Slug</label>
                                <input type="text" name="slug" class="form-control form-control-solid"
                                    value="{{ $createForm['slug'] }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control form-control-solid">{{ $createForm['description'] }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Price</label>
                                <input type="number" name="price" min="0" step="0.01"
                                    class="form-control form-control-solid" value="{{ $createForm['price'] }}"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Currency</label>
                                <input type="text" name="currency" maxlength="3"
                                    class="form-control form-control-solid" value="{{ $createForm['currency'] }}"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Interval</label>
                                <select name="interval" class="form-select form-select-solid">
                                    <option value="month"
                                        {{ $createForm['interval'] === 'month' ? 'selected' : '' }}>Month</option>
                                    <option value="year" {{ $createForm['interval'] === 'year' ? 'selected' : '' }}>
                                        Year</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Trial Days</label>
                                <input type="number" name="trial_days" min="0"
                                    class="form-control form-control-solid" value="{{ $createForm['trial_days'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" min="0"
                                    class="form-control form-control-solid" value="{{ $createForm['sort_order'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Flipbook Limit</label>
                                <input type="number" name="limits[flipbooks]" min="1"
                                    class="form-control form-control-solid" value="{{ $createForm['flipbooks'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Storage MB</label>
                                <input type="number" name="limits[storage_mb]" min="1"
                                    class="form-control form-control-solid" value="{{ $createForm['storage_mb'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stripe Price ID</label>
                                <input type="text" name="stripe_price_id" class="form-control form-control-solid"
                                    value="{{ $createForm['stripe_price_id'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stripe Product ID</label>
                                <input type="text" name="stripe_product_id"
                                    class="form-control form-control-solid"
                                    value="{{ $createForm['stripe_product_id'] }}">
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-6">
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="features[analytics]"
                                            value="1" {{ $createForm['analytics'] ? 'checked' : '' }}>
                                        <span class="form-check-label">Analytics</span>
                                    </label>
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="features[branding]"
                                            value="1" {{ $createForm['branding'] ? 'checked' : '' }}>
                                        <span class="form-check-label">Branding</span>
                                    </label>
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="is_active"
                                            value="1" {{ $createForm['is_active'] ? 'checked' : '' }}>
                                        <span class="form-check-label">Active immediately</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($plans as $plan)
        @php
            $isEditingPlan = old('billing_form') === 'edit' && (int) old('billing_plan_id') === (int) $plan->id;
            $editForm = [
                'name' => $isEditingPlan ? old('name', $plan->name) : $plan->name,
                'slug' => $isEditingPlan ? old('slug', $plan->slug) : $plan->slug,
                'description' => $isEditingPlan ? old('description', $plan->description) : $plan->description,
                'price' => $isEditingPlan ? old('price', $plan->price) : $plan->price,
                'currency' => $isEditingPlan ? old('currency', $plan->currency) : $plan->currency,
                'interval' => $isEditingPlan ? old('interval', $plan->interval) : $plan->interval,
                'trial_days' => $isEditingPlan ? old('trial_days', $plan->trial_days) : $plan->trial_days,
                'flipbooks' => $isEditingPlan
                    ? old('limits.flipbooks', data_get($plan->limits, 'flipbooks'))
                    : data_get($plan->limits, 'flipbooks'),
                'storage_mb' => $isEditingPlan
                    ? old('limits.storage_mb', data_get($plan->limits, 'storage_mb'))
                    : data_get($plan->limits, 'storage_mb'),
                'stripe_price_id' => $isEditingPlan
                    ? old('stripe_price_id', $plan->stripe_price_id)
                    : $plan->stripe_price_id,
                'stripe_product_id' => $isEditingPlan
                    ? old('stripe_product_id', $plan->stripe_product_id)
                    : $plan->stripe_product_id,
                'sort_order' => $isEditingPlan ? old('sort_order', $plan->sort_order) : $plan->sort_order,
                'analytics' => $isEditingPlan ? old('features.analytics') : $plan->hasFeature('analytics'),
                'branding' => $isEditingPlan ? old('features.branding') : $plan->hasFeature('branding'),
                'is_active' => $isEditingPlan ? old('is_active', $plan->is_active ? '1' : null) : $plan->is_active,
            ];
        @endphp
        <div class="modal fade" id="kt_modal_edit_plan_{{ $plan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.billing.plans.update', $plan) }}" data-swal-confirm
                        data-swal-title="Save {{ $plan->name }} changes?"
                        data-swal-text="The billing plan configuration will be updated immediately."
                        data-swal-confirm-text="Save changes" data-swal-cancel-text="Keep editing">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="billing_form" value="edit">
                        <input type="hidden" name="billing_plan_id" value="{{ $plan->id }}">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h2 class="fw-bold mb-1">Edit {{ $plan->name }}</h2>
                                <div class="text-muted fs-7">Update pricing, plan limits, activation state, and Stripe
                                    identifiers.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary"
                                data-bs-dismiss="modal">
                                {!! getIcon('cross', 'fs-1') !!}
                            </button>
                        </div>
                        <div class="modal-body py-10 px-lg-12">
                            <div class="row g-6">
                                <div class="col-md-6">
                                    <label class="form-label required">Name</label>
                                    <input type="text" name="name" class="form-control form-control-solid"
                                        value="{{ $editForm['name'] }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Slug</label>
                                    <input type="text" name="slug" class="form-control form-control-solid"
                                        value="{{ $editForm['slug'] }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="3" class="form-control form-control-solid">{{ $editForm['description'] }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Price</label>
                                    <input type="number" name="price" min="0" step="0.01"
                                        class="form-control form-control-solid" value="{{ $editForm['price'] }}"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Currency</label>
                                    <input type="text" name="currency" maxlength="3"
                                        class="form-control form-control-solid" value="{{ $editForm['currency'] }}"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Interval</label>
                                    <select name="interval" class="form-select form-select-solid">
                                        <option value="month"
                                            {{ $editForm['interval'] === 'month' ? 'selected' : '' }}>Month</option>
                                        <option value="year"
                                            {{ $editForm['interval'] === 'year' ? 'selected' : '' }}>Year</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Trial Days</label>
                                    <input type="number" name="trial_days" min="0"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['trial_days'] }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" min="0"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['sort_order'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Flipbook Limit</label>
                                    <input type="number" name="limits[flipbooks]" min="1"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['flipbooks'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Storage MB</label>
                                    <input type="number" name="limits[storage_mb]" min="1"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['storage_mb'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stripe Price ID</label>
                                    <input type="text" name="stripe_price_id"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['stripe_price_id'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stripe Product ID</label>
                                    <input type="text" name="stripe_product_id"
                                        class="form-control form-control-solid"
                                        value="{{ $editForm['stripe_product_id'] }}">
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-6">
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox"
                                                name="features[analytics]" value="1"
                                                {{ $editForm['analytics'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Analytics</span>
                                        </label>
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="features[branding]"
                                                value="1" {{ $editForm['branding'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Branding</span>
                                        </label>
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="is_active"
                                                value="1" {{ $editForm['is_active'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Active</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
        <script>
            (() => {
                const searchInput = document.getElementById('billing-plan-search');
                const planRows = Array.from(document.querySelectorAll('[data-plan-row]'));
                const emptyState = document.getElementById('billing-plan-empty');

                function filterPlans() {
                    const term = (searchInput?.value || '').trim().toLowerCase();
                    let visibleRows = 0;

                    planRows.forEach((row) => {
                        const matches = !term || row.dataset.planSearch.includes(term);
                        row.classList.toggle('d-none', !matches);

                        if (matches) {
                            visibleRows += 1;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('d-none', visibleRows !== 0 || planRows.length === 0);
                    }
                }

                if (searchInput) {
                    searchInput.addEventListener('input', filterPlans);
                    filterPlans();
                }

                const formContext = @json(old('billing_form'));
                const planId = @json(old('billing_plan_id'));

                if (formContext === 'create') {
                    const modal = document.getElementById('kt_modal_create_plan');

                    if (modal) {
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    }
                }

                if (formContext === 'edit' && planId) {
                    const modal = document.getElementById(`kt_modal_edit_plan_${planId}`);

                    if (modal) {
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    }
                }
            })();
        </script>
    @endpush

</x-default-layout>
