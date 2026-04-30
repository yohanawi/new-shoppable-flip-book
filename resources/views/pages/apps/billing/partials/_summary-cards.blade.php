@php
    $flipbookLimit = $currentPlan->limit('flipbooks');
    $storageLimitBytes = $billingManager->storageLimitBytes($currentPlan);

    // Calculate Flipbook Usage Percentage
    $flipbookPercent = 0;
    $isUnlimitedFlipbooks = empty($flipbookLimit) || $flipbookLimit === 'unlimited';
    if (!$isUnlimitedFlipbooks && $flipbookLimit > 0) {
        $flipbookPercent = min(100, round(($usage['flipbooks_count'] / $flipbookLimit) * 100));
    }
    $flipbookColor = $flipbookPercent > 85 ? 'danger' : ($flipbookPercent > 60 ? 'warning' : 'primary');

    // Calculate Storage Usage Percentage
    $storagePercent = 0;
    if ($storageLimitBytes > 0) {
        $storagePercent = min(100, round(($usage['storage_bytes'] / $storageLimitBytes) * 100));
    }
    $storageColor = $storagePercent > 85 ? 'danger' : ($storagePercent > 60 ? 'warning' : 'info');

    // Determine Status Colors
    $statusColor = 'success';
    $statusText = 'Active';
    if ($subscription?->onGracePeriod()) {
        $statusColor = 'warning';
        $statusText = 'Canceling';
    } elseif (!$subscription?->stripe_status) {
        $statusColor = 'primary';
        $statusText = 'Free Tier';
    } else {
        $statusText = ucfirst(str_replace('_', ' ', $subscription->stripe_status));
        if ($subscription->stripe_status === 'past_due' || $subscription->stripe_status === 'canceled') {
            $statusColor = 'danger';
        }
    }
@endphp

<div class="row g-6 g-xl-9 mb-8">
    <!-- 1. Current Plan Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card card-flush h-100 hover-elevate-up shadow-sm border-0">
            <div class="card-body p-9">
                <div class="d-flex align-items-center mb-5">
                    <div class="symbol symbol-50px me-4">
                        <span class="symbol-label bg-light-primary rounded-circle">
                            <i class="ki-duotone ki-crown fs-2x text-primary">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div>
                        <div class="text-gray-500 fw-semibold fs-7 uppercase tracking-wider">Current Plan</div>
                        <div class="fs-3 fw-bold text-gray-900">{{ $currentPlan->name }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-auto">
                    <span class="fs-6 fw-semibold text-gray-600">Price</span>
                    <span
                        class="badge badge-light-primary fs-6 fw-bold px-3 py-2">{{ $currentPlan->formattedPrice() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Subscription Status Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card card-flush h-100 hover-elevate-up shadow-sm border-0">
            <div class="card-body p-9">
                <div class="d-flex align-items-center mb-5">
                    <div class="symbol symbol-50px me-4">
                        <span class="symbol-label bg-light-{{ $statusColor }} rounded-circle">
                            <i class="ki-duotone ki-shield-tick fs-2x text-{{ $statusColor }}">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div>
                        <div class="text-gray-500 fw-semibold fs-7 uppercase tracking-wider">Status</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="bullet bullet-dot bg-{{ $statusColor }} h-10px w-10px animation-blink"></span>
                            <div class="fs-3 fw-bold text-gray-900">{{ $statusText }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-auto">
                    @if ($subscription?->onGracePeriod())
                        <div class="d-flex align-items-center text-gray-600 fs-6 fw-semibold">
                            <i class="ki-duotone ki-calendar-remove fs-4 me-2 text-warning"><span
                                    class="path1"></span><span class="path2"></span></i>
                            Ends {{ optional($subscription->ends_at)->format('d M Y') }}
                        </div>
                    @elseif ($upcomingInvoice)
                        <div class="d-flex align-items-center text-gray-600 fs-6 fw-semibold">
                            <i class="ki-duotone ki-calendar-tick fs-4 me-2 text-success"><span
                                    class="path1"></span><span class="path2"></span></i>
                            Next bill {{ $upcomingInvoice->date()->format('d M Y') }}
                        </div>
                    @else
                        <div class="d-flex align-items-center text-gray-500 fs-6 fw-semibold">
                            <i class="ki-duotone ki-minus-circle fs-4 me-2"><span class="path1"></span><span
                                    class="path2"></span></i>
                            No upcoming charge
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Flipbooks Usage Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card card-flush h-100 hover-elevate-up shadow-sm border-0">
            <div class="card-body p-9 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="text-gray-500 fw-semibold fs-7 uppercase tracking-wider">Flipbooks Used</div>
                        <i class="ki-duotone ki-book-open fs-2 text-gray-400"><span class="path1"></span><span
                                class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    </div>
                    <div class="fs-2 fw-bold text-gray-900 mb-1">
                        {{ $usage['flipbooks_count'] }}
                        <span class="text-muted fs-5 fw-semibold">/
                            {{ $isUnlimitedFlipbooks ? 'Unlimited' : $flipbookLimit }}</span>
                    </div>
                </div>

                @if (!$isUnlimitedFlipbooks)
                    <div class="w-100 mt-4">
                        <div class="d-flex flex-stack mb-2">
                            <span class="text-muted fs-7 fw-semibold">Capacity</span>
                            <span class="text-{{ $flipbookColor }} fs-7 fw-bold">{{ $flipbookPercent }}%</span>
                        </div>
                        <div class="progress h-6px bg-light-{{ $flipbookColor }}">
                            <div class="progress-bar bg-{{ $flipbookColor }}" role="progressbar"
                                style="width: {{ $flipbookPercent }}%" aria-valuenow="{{ $flipbookPercent }}"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @else
                    <div class="w-100 mt-4">
                        <div class="badge badge-light-success fw-bold w-100 py-3">Unlimited Publishing Enabled</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 4. Storage Usage Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card card-flush h-100 hover-elevate-up shadow-sm border-0">
            <div class="card-body p-9 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="text-gray-500 fw-semibold fs-7 uppercase tracking-wider">Storage Used</div>
                        <i class="ki-duotone ki-hard-drive fs-2 text-gray-400"><span class="path1"></span><span
                                class="path2"></span></i>
                    </div>
                    <div class="fs-2 fw-bold text-gray-900 mb-1">
                        {{ $billingManager->formatBytes($usage['storage_bytes']) }}
                        <span class="text-muted fs-5 fw-semibold">/
                            {{ $billingManager->formatBytes($storageLimitBytes) }}</span>
                    </div>
                </div>

                <div class="w-100 mt-4">
                    <div class="d-flex flex-stack mb-2">
                        <span class="text-muted fs-7 fw-semibold">Space occupied</span>
                        <span class="text-{{ $storageColor }} fs-7 fw-bold">{{ $storagePercent }}%</span>
                    </div>
                    <div class="progress h-6px bg-light-{{ $storageColor }}">
                        <div class="progress-bar bg-{{ $storageColor }}" role="progressbar"
                            style="width: {{ $storagePercent }}%" aria-valuenow="{{ $storagePercent }}"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
