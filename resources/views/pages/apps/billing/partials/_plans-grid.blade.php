<div class="row g-6 g-xl-9">
    @foreach ($plans as $plan)
        @php
            $canSubscribe = $plan->exists && $plan->getKey();
            $isCurrentPlan = (int) $plan->id === (int) $currentPlan->id;
            $planRequest = $paymentRequestsByPlan->get($plan->id);
            $hasStripeCheckout = $stripeConfigured && filled($plan->stripe_price_id);
            $planButtonLabel =
                $subscription && !$currentPlan->isFree()
                    ? ((float) $plan->price > (float) $currentPlan->price
                        ? 'Upgrade to ' . $plan->name
                        : 'Change to ' . $plan->name)
                    : 'Checkout ' . $plan->name;
        @endphp

        <div class="col-xl-4">
            <div class="card card-flush h-100 hover-elevate-up {{ $isCurrentPlan ? 'border-2 border-primary shadow-sm' : 'border-0 shadow-sm' }}">
                <div class="card-body p-9 d-flex flex-column">
                    <!-- Header Section -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <h3 class="fs-2x fw-bold text-gray-900 mb-0">{{ $plan->name }}</h3>
                                @if ($isCurrentPlan)
                                    <span class="badge badge-primary fw-bold text-uppercase fs-8 px-3 py-1">Current</span>
                                @endif
                            </div>
                            <!-- Payment Method Badge -->
                            <span class="badge badge-light-{{ $hasStripeCheckout ? 'success' : 'warning' }} px-3 py-2 fw-bold fs-8">
                                <i class="ki-duotone ki-{{ $hasStripeCheckout ? 'credit-cart text-success' : 'document text-warning' }} fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ $hasStripeCheckout ? 'Stripe Checkout' : 'Manual Review' }}
                            </span>
                        </div>
                        <div class="text-gray-500 fs-6 fw-semibold">{{ $plan->description }}</div>
                    </div>

                    <!-- Price Section -->
                    <div class="mb-6">
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="fs-1 fw-bolder text-gray-900">{{ $plan->formattedPrice() }}</span>
                            <span class="fs-6 fw-semibold text-gray-500">/{{ $plan->interval }}</span>
                        </div>
                        
                        @if ($plan->trial_days)
                            <div class="mt-3">
                                <span class="badge badge-light-primary fw-bold px-3 py-2 text-primary fs-7">
                                    <i class="ki-duotone ki-gift fs-5 me-1 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                    {{ $plan->trial_days }} days free trial via Stripe
                                </span>
                            </div>
                        @else
                            <div class="text-gray-500 fs-7 fw-semibold mt-3 px-1">
                                Choose the route that matches your billing preference.
                            </div>
                        @endif
                    </div>

                    <div class="separator separator-dashed border-gray-300 mb-7"></div>

                    <!-- Features Section -->
                    <div class="d-flex flex-column gap-4 mb-8">
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-check-circle fs-2 text-success me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-gray-700 fw-semibold fs-6">
                                <span class="fw-bold text-gray-900">{{ $plan->limit('flipbooks') ?? 'Unlimited' }}</span> Flipbooks
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-check-circle fs-2 text-success me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-gray-700 fw-semibold fs-6">
                                <span class="fw-bold text-gray-900">{{ $billingManager->formatBytes($billingManager->storageLimitBytes($plan)) }}</span> Storage
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-{{ $plan->hasFeature('analytics') ? 'check-circle text-success' : 'cross-circle text-muted' }} fs-2 me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="fw-semibold fs-6 {{ $plan->hasFeature('analytics') ? 'text-gray-700' : 'text-gray-400' }}">
                                Analytics Integration
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-{{ $plan->hasFeature('branding') ? 'check-circle text-success' : 'cross-circle text-muted' }} fs-2 me-3"><span class="path1"></span><span class="path2"></span></i>
                            <span class="fw-semibold fs-6 {{ $plan->hasFeature('branding') ? 'text-gray-700' : 'text-gray-400' }}">
                                Custom Branding
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons Section -->
                    <div class="mt-auto d-grid gap-3">
                        @if ($canSubscribe)
                            @if ($isCurrentPlan)
                                <button type="button" class="btn btn-light-primary fw-bold" disabled>
                                    <i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    Active Plan
                                </button>
                            @elseif ($plan->isFree())
                                <form method="POST" action="{{ route('billing.subscribe', $plan) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-light fw-bold w-100">Downgrade to Free</button>
                                </form>
                            @elseif ($openPaymentRequest)
                                <a href="{{ route('billing.payments.show', $openPaymentRequest) }}" class="btn btn-light-warning fw-bold">
                                    {{ (int) $openPaymentRequest->plan_id === (int) $plan->id ? 'Manual Payment Under Review' : 'Another Request Under Review' }}
                                </a>
                            @elseif ($hasStripeCheckout)
                                <form method="POST" action="{{ route('billing.subscribe', $plan) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary fw-bold w-100">{{ $planButtonLabel }}</button>
                                </form>
                                <a href="{{ route('billing.payments.create', ['plan' => $plan->id]) }}" class="btn btn-light-primary fw-bold text-hover-primary">
                                    Submit Manual Payment Instead
                                </a>
                                @if ($planRequest && $planRequest->isRejected())
                                    <a href="{{ route('billing.payments.show', $planRequest) }}" class="btn btn-light-danger fw-bold">
                                        Review Rejected Request
                                    </a>
                                @endif
                            @elseif ($planRequest && $planRequest->isRejected())
                                <a href="{{ route('billing.payments.show', $planRequest) }}" class="btn btn-light-danger fw-bold">
                                    Resubmit {{ $plan->name }}
                                </a>
                            @else
                                <a href="{{ route('billing.payments.create', ['plan' => $plan->id]) }}" class="btn btn-primary fw-bold">
                                    Submit Manual Payment
                                </a>
                            @endif
                        @else
                            <button type="button" class="btn btn-light fw-bold" disabled>Plan setup required</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>