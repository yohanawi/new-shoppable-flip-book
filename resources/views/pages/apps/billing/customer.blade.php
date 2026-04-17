<x-default-layout>

    @section('title')
        Billing
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.index') }}
    @endsection

    @php
        $flipbookLimit = $currentPlan->limit('flipbooks');
        $storageLimitBytes = $billingManager->storageLimitBytes($currentPlan);
        $hasUnseededPlans = $plans->contains(fn($plan) => !$plan->exists || !$plan->getKey());
    @endphp

    @if (session('success'))
        <div class="alert alert-success mb-8">{{ session('success') }}</div>
    @endif

    @if (session('status'))
        <div class="alert alert-info mb-8">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-bold mb-2">Billing action could not be completed.</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($hasUnseededPlans)
        <div class="alert alert-warning mb-8">
            Billing plans have not been seeded yet. Run <code>php artisan db:seed --class=BillingSeeder</code> to enable
            plan changes.
        </div>
    @endif

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Current Plan</div>
                    <div class="fs-2 fw-bold text-gray-900 mb-3">{{ $currentPlan->name }}</div>
                    <div class="badge badge-light-primary">{{ $currentPlan->formattedPrice() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Subscription Status</div>
                    <div class="fs-2 fw-bold text-gray-900 mb-3">
                        {{ $subscription?->stripe_status ? ucfirst(str_replace('_', ' ', $subscription->stripe_status)) : 'Free tier' }}
                    </div>
                    @if ($subscription?->onGracePeriod())
                        <div class="badge badge-light-warning">Ends
                            {{ optional($subscription->ends_at)->format('d M Y') }}</div>
                    @elseif ($upcomingInvoice)
                        <div class="badge badge-light-success">Next bill {{ $upcomingInvoice->date()->format('d M Y') }}
                        </div>
                    @else
                        <div class="badge badge-light-dark">No upcoming charge</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Flipbooks Used</div>
                    <div class="fs-2 fw-bold text-gray-900 mb-3">
                        {{ $usage['flipbooks_count'] }}
                        <span class="text-muted fs-6">/ {{ $flipbookLimit ?? 'Unlimited' }}</span>
                    </div>
                    <div class="text-muted fs-7">Upgrade when you need more publishing capacity.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Storage Used</div>
                    <div class="fs-2 fw-bold text-gray-900 mb-3">
                        {{ $billingManager->formatBytes($usage['storage_bytes']) }}</div>
                    <div class="text-muted fs-7">Limit: {{ $billingManager->formatBytes($storageLimitBytes) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-6">
            <div>
                <h3 class="mb-2">Billing Overview</h3>
                <div class="text-muted mb-4">Manage plan changes, payment methods, invoices, and Stripe customer access
                    from one place.</div>
                <div class="d-flex flex-wrap gap-3">
                    <span
                        class="badge badge-light {{ $currentPlan->hasFeature('analytics') ? 'badge-light-success' : 'badge-light-danger' }}">
                        Analytics {{ $currentPlan->hasFeature('analytics') ? 'Enabled' : 'Disabled' }}
                    </span>
                    <span
                        class="badge badge-light {{ $currentPlan->hasFeature('branding') ? 'badge-light-success' : 'badge-light-danger' }}">
                        Branding {{ $currentPlan->hasFeature('branding') ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-start">
                <a href="{{ route('billing.portal') }}" class="btn btn-light-primary">Stripe Portal</a>
                @if ($subscription && !$subscription->onGracePeriod())
                    <form method="POST" action="{{ route('billing.subscription.cancel') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-danger">Cancel Subscription</button>
                    </form>
                @elseif ($subscription && $subscription->onGracePeriod())
                    <form method="POST" action="{{ route('billing.subscription.resume') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-success">Resume Subscription</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-6 g-xl-9 mb-8">
        @foreach ($plans as $plan)
            @php
                $canSubscribe = $plan->exists && $plan->getKey();
            @endphp
            <div class="col-xl-4">
                <div
                    class="card h-100 {{ (int) $plan->id === (int) $currentPlan->id ? 'border border-primary' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-5">
                            <div>
                                <h3 class="mb-1">{{ $plan->name }}</h3>
                                <div class="text-muted fs-7">{{ $plan->description }}</div>
                            </div>
                            @if ((int) $plan->id === (int) $currentPlan->id)
                                <span class="badge badge-light-primary">Current</span>
                            @endif
                        </div>

                        <div class="fs-1 fw-bold text-gray-900 mb-4">{{ $plan->formattedPrice() }}
                            <span class="fs-7 text-muted">/{{ $plan->interval }}</span>
                        </div>

                        <div class="mb-6">
                            <div class="text-gray-700 mb-2">Flipbooks: {{ $plan->limit('flipbooks') ?? 'Unlimited' }}
                            </div>
                            <div class="text-gray-700 mb-2">Storage:
                                {{ $billingManager->formatBytes($billingManager->storageLimitBytes($plan)) }}</div>
                            <div class="text-gray-700 mb-2">Analytics:
                                {{ $plan->hasFeature('analytics') ? 'Yes' : 'No' }}</div>
                            <div class="text-gray-700">Branding: {{ $plan->hasFeature('branding') ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div class="mt-auto">
                            @if ($canSubscribe)
                                <form method="POST" action="{{ route('billing.subscribe', $plan) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn {{ (int) $plan->id === (int) $currentPlan->id ? 'btn-light' : 'btn-primary' }} w-100"
                                        {{ (int) $plan->id === (int) $currentPlan->id ? 'disabled' : '' }}>
                                        @if ($plan->isFree())
                                            Downgrade to Free
                                        @elseif ($subscription)
                                            Switch to {{ $plan->name }}
                                        @else
                                            Start {{ $plan->name }}
                                        @endif
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-light w-100" disabled>Plan setup required</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Payment Methods</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if (!$stripeConfigured)
                        <div class="alert alert-warning">Stripe keys are not configured yet. Add your Stripe keys to
                            enable payment methods.</div>
                    @endif

                    @forelse ($paymentMethods as $paymentMethod)
                        <div class="border border-gray-200 rounded p-4 mb-4">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                                <div>
                                    <div class="fw-bold text-gray-900 mb-1">
                                        {{ ucfirst($paymentMethod->card?->brand ?? $paymentMethod->type) }} ending in
                                        {{ $paymentMethod->card?->last4 ?? '----' }}</div>
                                    <div class="text-muted fs-7">Expires
                                        {{ sprintf('%02d', (int) ($paymentMethod->card?->exp_month ?? 0)) }}/{{ $paymentMethod->card?->exp_year ?? '----' }}
                                    </div>
                                    @if ($defaultPaymentMethod && $defaultPaymentMethod->id === $paymentMethod->id)
                                        <div class="badge badge-light-success mt-3">Default payment method</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    @if (!$defaultPaymentMethod || $defaultPaymentMethod->id !== $paymentMethod->id)
                                        <form method="POST"
                                            action="{{ route('billing.payment-methods.default', $paymentMethod->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-primary">Set
                                                Default</button>
                                        </form>
                                    @endif
                                    <form method="POST"
                                        action="{{ route('billing.payment-methods.destroy', $paymentMethod->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No saved payment methods yet.</div>
                    @endforelse

                    @if ($stripeConfigured && $setupIntent && $stripePublicKey)
                        <div class="separator my-6"></div>
                        <form id="payment-method-form" method="POST"
                            action="{{ route('billing.payment-methods.store') }}">
                            @csrf
                            <input type="hidden" name="payment_method" id="payment_method">
                            <div class="mb-4">
                                <label class="form-label">Add a Card</label>
                                <div id="card-element" class="form-control form-control-solid py-4"></div>
                                <div id="card-errors" class="text-danger fs-7 mt-2"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Card</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Recent Invoices</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @forelse ($cashierInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->date()->format('d M Y') }}</td>
                                        <td>
                                            <span
                                                class="badge badge-light-{{ $invoice->paid ? 'success' : 'warning' }}">
                                                {{ ucfirst($invoice->status ?? ($invoice->paid ? 'paid' : 'open')) }}
                                            </span>
                                        </td>
                                        <td>{{ $invoice->total() }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('billing.invoices.download', $invoice->id) }}"
                                                class="btn btn-sm btn-light-primary">Download</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-8">No invoices available
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

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Payment Activity</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Processed</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-700">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ optional($transaction->processed_at ?? $transaction->created_at)->format('d M Y H:i') }}
                                </td>
                                <td>{{ ucfirst($transaction->type) }}</td>
                                <td><span class="badge badge-light">{{ ucfirst($transaction->status) }}</span></td>
                                <td>{{ strtoupper($transaction->currency) }}
                                    {{ number_format($transaction->amount / 100, 2) }}</td>
                                <td>{{ $transaction->description ?: 'Stripe transaction' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-8">No payment activity has been
                                    recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($stripeConfigured && $setupIntent && $stripePublicKey)
        @push('scripts')
            <script src="https://js.stripe.com/v3/"></script>
            <script>
                (function() {
                    const form = document.getElementById('payment-method-form');
                    if (!form) {
                        return;
                    }

                    const stripe = Stripe(@json($stripePublicKey));
                    const elements = stripe.elements();
                    const card = elements.create('card', {
                        hidePostalCode: true,
                    });

                    card.mount('#card-element');

                    form.addEventListener('submit', async function(event) {
                        event.preventDefault();

                        const errorElement = document.getElementById('card-errors');
                        errorElement.textContent = '';

                        const result = await stripe.confirmCardSetup(@json($setupIntent->client_secret), {
                            payment_method: {
                                card: card,
                            },
                        });

                        if (result.error) {
                            errorElement.textContent = result.error.message || 'Unable to save card.';
                            return;
                        }

                        document.getElementById('payment_method').value = result.setupIntent.payment_method;
                        form.submit();
                    });
                })();
            </script>
        @endpush
    @endif

</x-default-layout>
