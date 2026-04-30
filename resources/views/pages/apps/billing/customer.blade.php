<x-default-layout>

    @section('title')
        Billing
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.index') }}
    @endsection

    @php
        $hasUnseededPlans = $plans->contains(fn($plan) => !$plan->exists || !$plan->getKey());
        $flipbookLimit = $currentPlan->limit('flipbooks');
        $storageLimitBytes = $billingManager->storageLimitBytes($currentPlan);
        $latestTransaction = $transactions->first();
    @endphp

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')

    @if ($hasUnseededPlans)
        <div class="alert alert-warning mb-8">
            Billing plans have not been seeded yet. Run <code>php artisan db:seed --class=BillingSeeder</code> to enable
            plan changes.
        </div>
    @endif

    @include('pages.apps.billing.partials._summary-cards')


    <div class="card mb-8">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Recent Payment Requests</h2>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('billing.payments.history') }}" class="btn btn-sm btn-light-primary">View
                    All</a>
            </div>
        </div>
        <div class="card-body pt-0">
            @if ($recentPaymentRequests->isNotEmpty())
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Request</th>
                                <th>Plan</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @foreach ($recentPaymentRequests as $paymentRequest)
                                @php
                                    $requestTone = match ($paymentRequest->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'under_review' => 'warning',
                                        default => 'primary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $paymentRequest->requestNumber() }}</td>
                                    <td>{{ $paymentRequest->plan?->name ?? 'Unknown plan' }}</td>
                                    <td>{{ $paymentRequest->gatewayLabel() }}</td>
                                    <td><span
                                            class="badge badge-light-{{ $requestTone }}">{{ $paymentRequest->statusLabel() }}</span>
                                    </td>
                                    <td>{{ strtoupper($paymentRequest->currency) }}
                                        {{ number_format((float) $paymentRequest->amount, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('billing.payments.show', $paymentRequest) }}"
                                            class="btn btn-sm btn-light-primary">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted py-8">Payment requests will appear here after you submit one for manual
                    approval.
                </div>
            @endif
        </div>
    </div>

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Latest Payment Request</div>
                    @if ($latestPaymentRequest)
                        @php
                            $requestTone = match ($latestPaymentRequest->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'under_review' => 'warning',
                                default => 'primary',
                            };
                        @endphp
                        <div class="fs-3 fw-bold text-gray-900 mb-3">{{ $latestPaymentRequest->requestNumber() }}</div>
                        <div class="badge badge-light-{{ $requestTone }} mb-4">
                            {{ $latestPaymentRequest->statusLabel() }}</div>
                        <div class="text-muted fs-7 mb-2">{{ $latestPaymentRequest->plan?->name ?? 'Plan' }} via
                            {{ $latestPaymentRequest->gatewayLabel() }}</div>
                        <div class="text-muted fs-7 mb-5">Submitted
                            {{ optional($latestPaymentRequest->submitted_at ?? $latestPaymentRequest->created_at)->format('d M Y H:i') }}
                        </div>
                        <a href="{{ route('billing.payments.show', $latestPaymentRequest) }}"
                            class="btn btn-light-primary btn-sm">Track Request</a>
                    @else
                        <div class="text-muted mb-5">You have not submitted any manual payment requests yet.</div>
                        <a href="{{ route('billing.payments.create') }}" class="btn btn-primary btn-sm">Submit
                            Payment</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Latest Invoice</div>
                    @if ($latestInvoice)
                        <div class="fs-3 fw-bold text-gray-900 mb-3">{{ $latestInvoice->total() }}</div>
                        <div class="badge badge-light-{{ $latestInvoice->paid ? 'success' : 'warning' }} mb-4">
                            {{ ucfirst($latestInvoice->status ?? ($latestInvoice->paid ? 'paid' : 'open')) }}
                        </div>
                        <div class="text-muted fs-7 mb-5">Issued {{ $latestInvoice->date()->format('d M Y') }}</div>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light-primary btn-sm">View
                            Invoices</a>
                    @else
                        <div class="text-muted mb-5">No invoices have been generated for this account yet.</div>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light btn-sm">Open Invoice
                            Center</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Recent Payment Activity</div>
                    @if ($latestTransaction)
                        <div class="fs-3 fw-bold text-gray-900 mb-3">{{ strtoupper($latestTransaction->currency) }}
                            {{ number_format($latestTransaction->amount / 100, 2) }}</div>
                        <div class="badge badge-light mb-4">{{ ucfirst($latestTransaction->status) }}</div>
                        <div class="text-muted fs-7 mb-5">{{ $latestTransaction->description ?: 'Stripe transaction' }}
                        </div>
                        <a href="{{ route('billing.invoices.index') }}" class="btn btn-light-primary btn-sm">View
                            Activity</a>
                    @else
                        <div class="text-muted mb-5">No payment activity has been recorded yet.</div>
                        <a href="{{ route('billing.payment-methods.index') }}" class="btn btn-light btn-sm">Review
                            Payment Setup</a>
                    @endif
                </div>
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
