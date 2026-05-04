<x-default-layout>

    @section('title')
        Billing Payment Methods
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payment-methods.index') }}
    @endsection

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')
    @include('pages.apps.billing.partials._summary-cards')

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-xl-row justify-content-between gap-6 items-center">
            <div>
                <h3 class="mt-2">Payment Methods</h3>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <a href="{{ route('billing.plans') }}" class="btn btn-light">View Plans</a>
                <a href="{{ route('billing.invoices.index') }}" class="btn btn-light-primary">Invoices and Activity</a>
                @if ($stripeConfigured)
                    <a href="{{ route('billing.portal') }}" class="btn btn-primary">Stripe Portal</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-6 g-xl-9">
        <div class="col-xl-8">
            <!--begin::Payment Methods-->
            <div class="card card-flush h-xl-100 shadow-sm">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900 fs-3">Saved Payment Methods</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Manage your cards and primary billing
                            source</span>
                    </h3>
                    <!--end::Title-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-5">
                    @if (!$stripeConfigured)
                        <!--begin::Alert-->
                        <div class="alert alert-dismissible bg-light-warning d-flex flex-column flex-sm-row p-5 mb-10">
                            <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4 mb-5 mb-sm-0"><span
                                    class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <div class="d-flex flex-column pe-0 pe-sm-10">
                                <h4 class="fw-bold">Action Required</h4>
                                <span>Stripe keys are not configured. Please visit your <a href="#"
                                        class="fw-bolder text-warning">API Settings</a> to enable payments.</span>
                            </div>
                        </div>
                        <!--end::Alert-->
                    @endif

                    <!--begin::List-->
                    <div class="mb-10">
                        @forelse ($paymentMethods as $paymentMethod)
                            <!--begin::Payment Method Item-->
                            <div class="d-flex flex-stack flex-wrap py-5 border-bottom border-gray-100">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Icon-->
                                    <div class="symbol symbol-45px symbol-light me-5">
                                        <span class="symbol-label">
                                            @php
                                                $brand = strtolower($paymentMethod->card?->brand ?? 'default');
                                                $iconClass = match ($brand) {
                                                    'visa' => 'fa-brands fa-cc-visa text-primary',
                                                    'mastercard' => 'fa-brands fa-cc-mastercard text-danger',
                                                    'amex' => 'fa-brands fa-cc-amex text-info',
                                                    default => 'fa-solid fa-credit-card text-gray-600',
                                                };
                                            @endphp
                                            <i class="{{ $iconClass }} fs-2x"></i>
                                        </span>
                                    </div>
                                    <!--end::Icon-->

                                    <!--begin::Info-->
                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="fs-6 fw-bold text-gray-900 me-2">
                                                {{ ucfirst($paymentMethod->card?->brand ?? $paymentMethod->type) }}
                                                <span class="text-gray-400 fw-normal">••••
                                                    {{ $paymentMethod->card?->last4 ?? '----' }}</span>
                                            </span>
                                            @if ($defaultPaymentMethod && $defaultPaymentMethod->id === $paymentMethod->id)
                                                <span class="badge badge-light-success fs-9 fw-bold">DEFAULT</span>
                                            @endif
                                        </div>
                                        <span class="fs-7 text-muted fw-semibold">
                                            Expires
                                            {{ sprintf('%02d', (int) ($paymentMethod->card?->exp_month ?? 0)) }}/{{ $paymentMethod->card?->exp_year ?? '----' }}
                                        </span>
                                    </div>
                                    <!--end::Info-->
                                </div>
                                <!--end::Details-->

                                <!--begin::Actions-->
                                <div class="d-flex justify-content-end align-items-center">
                                    @if (!$defaultPaymentMethod || $defaultPaymentMethod->id !== $paymentMethod->id)
                                        <form method="POST"
                                            action="{{ route('billing.payment-methods.default', $paymentMethod->id) }}"
                                            class="me-2">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-light btn-active-light-primary fw-bold">Set
                                                Default</button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                        action="{{ route('billing.payment-methods.destroy', $paymentMethod->id) }}"
                                        data-delete-swal-title="Delete payment method?"
                                        data-delete-swal-text="This action is permanent and cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-icon btn-light-danger btn-active-danger">
                                            <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span
                                                    class="path2"></span><span class="path3"></span><span
                                                    class="path4"></span><span class="path5"></span></i>
                                        </button>
                                    </form>
                                </div>
                                <!--end::Actions-->
                            </div>
                            <!--end::Payment Method Item-->
                        @empty
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                                <i class="ki-duotone ki-credit-cart fs-2tx text-primary me-4"><span
                                        class="path1"></span><span class="path2"></span></i>
                                <div class="d-flex flex-stack flex-grow-1">
                                    <div class="fw-semibold text-gray-700">
                                        <div class="fs-6">No saved payment methods yet.</div>
                                        <div class="fs-7">Add your first card below to start your subscription.</div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <!--end::List-->

                    @if ($stripeConfigured && $setupIntent && $stripePublicKey)
                        <!--begin::Form Section-->
                        <div class="bg-gray-100 rounded-3 p-8 mt-10">
                            <div class="mb-8">
                                <h4 class="text-gray-900 fw-bold mb-1">Add New Card</h4>
                                <p class="fs-7 text-muted fw-semibold">Your payment information is encrypted and
                                    processed securely by Stripe.</p>
                            </div>

                            <form id="payment-method-form" method="POST"
                                action="{{ route('billing.payment-methods.store') }}">
                                @csrf
                                <input type="hidden" name="payment_method" id="payment_method">

                                <div class="fv-row mb-8">
                                    <label class="form-label fs-6 fw-bold text-gray-700">Card Details</label>
                                    <!-- Stripe Card Element Mount Point -->
                                    <div id="card-element" class="form-control form-control-solid bg-white py-4 border">
                                    </div>
                                    <div id="card-errors" class="text-danger fs-7 mt-2 fw-semibold"></div>
                                </div>

                                <div class="d-flex flex-stack">
                                    <div class="me-5">
                                        <img src="https://preview.keenthemes.com/metronic8/demo1/assets/media/svg/card-logos/visa.svg"
                                            class="h-20px me-2" alt="">
                                        <img src="https://preview.keenthemes.com/metronic8/demo1/assets/media/svg/card-logos/mastercard.svg"
                                            class="h-20px me-2" alt="">
                                        <img src="https://preview.keenthemes.com/metronic8/demo1/assets/media/svg/card-logos/american-express.svg"
                                            class="h-20px" alt="">
                                    </div>
                                    <button type="submit" id="card-button"
                                        data-secret="{{ $setupIntent->client_secret }}"
                                        class="btn btn-primary fw-bold">
                                        <span class="indicator-label">Add Card</span>
                                        <span class="indicator-progress">Please wait... <span
                                                class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!--end::Form Section-->
                    @endif
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Payment Methods-->
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold text-gray-900 fs-3 mb-2">Manual Billing Support</div>
                    <div class="text-muted fs-7 mb-5">If Stripe checkout is not the right fit for this customer, the
                        manual payment request flow remains available for plan changes.</div>
                    <a href="{{ route('billing.payments.create') }}" class="btn btn-light-primary w-100 mb-3">Submit
                        Manual Payment</a>
                    <a href="{{ route('billing.payments.history') }}" class="btn btn-light w-100">View Payment
                        Requests</a>
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
