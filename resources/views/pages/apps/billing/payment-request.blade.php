<x-default-layout>

    @section('title')
        Submit Payment Request
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payments.create') }}
    @endsection

    @php
        $billingActiveSection = 'payment-requests';
        $selectedPlan = $selectedPlan ?: $plans->first();
    @endphp

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-6">
            <div>
                <h3 class="mb-2">Submit a Manual Payment</h3>
                <div class="text-muted mb-4">Choose the plan you want, enter your payment reference, and upload the
                    receipt for admin review.</div>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge badge-light-primary">Current plan: {{ $currentPlan->name }}</span>
                    <span class="badge badge-light-info">Manual review required</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-start">
                <a href="{{ route('billing.plans') }}" class="btn btn-light">Plans</a>
                <a href="{{ route('billing.payments.history') }}" class="btn btn-light-primary">Payment History</a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('billing.payments.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-6 g-xl-9 mb-8">
            <div class="col-xl-8">
                <div class="card mb-8">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h2>Select a Plan</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-6">
                            @foreach ($plans as $plan)
                                <div class="col-md-6">
                                    <label
                                        class="card border border-gray-300 border-hover-primary h-100 cursor-pointer">
                                        <div class="card-body">
                                            <div class="form-check form-check-custom form-check-solid mb-4">
                                                <input class="form-check-input" type="radio" name="plan_id"
                                                    value="{{ $plan->id }}" data-plan-name="{{ $plan->name }}"
                                                    data-plan-price="{{ $plan->formattedPrice() }}/{{ $plan->interval }}"
                                                    data-plan-flipbooks="{{ $plan->limit('flipbooks') ?? 'Unlimited' }}"
                                                    data-plan-storage="{{ $plan->limit('storage_mb') ? number_format($plan->limit('storage_mb')) . ' MB' : 'Unlimited' }}"
                                                    data-plan-branding="{{ $plan->hasFeature('branding') ? 'Included' : 'Not included' }}"
                                                    {{ (int) old('plan_id', $selectedPlan?->id) === (int) $plan->id ? 'checked' : '' }}>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start mb-4">
                                                <div>
                                                    <h3 class="mb-1">{{ $plan->name }}</h3>
                                                    <div class="text-muted fs-7">{{ $plan->description }}</div>
                                                </div>
                                                <span
                                                    class="badge badge-light-primary">{{ $plan->formattedPrice() }}/{{ $plan->interval }}</span>
                                            </div>
                                            <div class="text-gray-700 mb-2">Flipbooks:
                                                {{ $plan->limit('flipbooks') ?? 'Unlimited' }}</div>
                                            <div class="text-gray-700 mb-2">Storage:
                                                {{ $plan->limit('storage_mb') ? number_format($plan->limit('storage_mb')) . ' MB' : 'Unlimited' }}
                                            </div>
                                            <div class="text-gray-700">Analytics:
                                                {{ $plan->hasFeature('analytics') ? 'Included' : 'Not included' }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h2>Payment Details</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-6">
                            <label for="transaction_reference" class="form-label fw-bold">Transaction Reference</label>
                            <input type="text" id="transaction_reference" name="transaction_reference"
                                class="form-control form-control-solid" value="{{ old('transaction_reference') }}"
                                placeholder="Enter the payment reference or transfer ID">
                        </div>

                        <div class="mb-6">
                            <label for="receipt" class="form-label fw-bold">Receipt or Payment Proof</label>
                            <input type="file" id="receipt" name="receipt" class="form-control form-control-solid"
                                accept=".jpg,.jpeg,.png,.pdf,.webp">
                            <div class="text-muted fs-7 mt-2">Accepted formats: JPG, PNG, PDF, WEBP. Maximum 10 MB.
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="customer_note" class="form-label fw-bold">Note for Billing Team</label>
                            <textarea id="customer_note" name="customer_note" rows="4" class="form-control form-control-solid"
                                placeholder="Add anything the reviewer should know about this payment.">{{ old('customer_note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card position-sticky" style="top: 2rem;">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h2>Summary</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if ($selectedPlan)
                            <div class="mb-6">
                                <div class="text-muted fs-7 mb-2">Selected plan</div>
                                <div id="selected-plan-name" class="fw-bold fs-3 text-gray-900">
                                    {{ $selectedPlan->name }}</div>
                                <div id="selected-plan-price" class="text-muted">
                                    {{ $selectedPlan->formattedPrice() }}/{{ $selectedPlan->interval }}</div>
                            </div>
                            <div class="separator my-6"></div>
                            <div class="mb-4 d-flex justify-content-between">
                                <span class="text-gray-600">Flipbooks</span>
                                <span id="selected-plan-flipbooks"
                                    class="fw-semibold">{{ $selectedPlan->limit('flipbooks') ?? 'Unlimited' }}</span>
                            </div>
                            <div class="mb-4 d-flex justify-content-between">
                                <span class="text-gray-600">Storage</span>
                                <span id="selected-plan-storage"
                                    class="fw-semibold">{{ $selectedPlan->limit('storage_mb') ? number_format($selectedPlan->limit('storage_mb')) . ' MB' : 'Unlimited' }}</span>
                            </div>
                            <div class="mb-8 d-flex justify-content-between">
                                <span class="text-gray-600">Branding</span>
                                <span id="selected-plan-branding"
                                    class="fw-semibold">{{ $selectedPlan->hasFeature('branding') ? 'Included' : 'Not included' }}</span>
                            </div>
                        @endif

                        <div class="alert alert-light-primary">
                            Manual payments are activated only after admin approval. You can track the decision and
                            resubmit if more proof is needed.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Submit Payment Request</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function() {
                const planInputs = document.querySelectorAll('input[name="plan_id"]');
                const summaryTargets = {
                    name: document.getElementById('selected-plan-name'),
                    price: document.getElementById('selected-plan-price'),
                    flipbooks: document.getElementById('selected-plan-flipbooks'),
                    storage: document.getElementById('selected-plan-storage'),
                    branding: document.getElementById('selected-plan-branding'),
                };

                const updateSummary = function(input) {
                    if (!input || !summaryTargets.name) {
                        return;
                    }

                    summaryTargets.name.textContent = input.dataset.planName || '';
                    summaryTargets.price.textContent = input.dataset.planPrice || '';
                    summaryTargets.flipbooks.textContent = input.dataset.planFlipbooks || '';
                    summaryTargets.storage.textContent = input.dataset.planStorage || '';
                    summaryTargets.branding.textContent = input.dataset.planBranding || '';
                };

                planInputs.forEach(function(input) {
                    input.addEventListener('change', function() {
                        updateSummary(input);
                    });

                    if (input.checked) {
                        updateSummary(input);
                    }
                });
            })();
        </script>
    @endpush

</x-default-layout>
