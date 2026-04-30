<x-default-layout>

    @section('title')
        Payment Request Status
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payments.show', $paymentRequest) }}
    @endsection

    @php
        $billingActiveSection = 'payment-requests';

        // Define theme parameters based on status for a richer UI
        $theme = match ($paymentRequest->status) {
            'approved' => ['color' => 'success', 'icon' => 'fa-check-circle'],
            'rejected' => ['color' => 'danger', 'icon' => 'fa-times-circle'],
            'under_review' => ['color' => 'warning', 'icon' => 'fa-clock'],
            default => ['color' => 'primary', 'icon' => 'fa-info-circle'],
        };
    @endphp

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')

    <!-- begin::Hero Status Panel -->
    <div
        class="notice d-flex bg-light-{{ $theme['color'] }} rounded border-{{ $theme['color'] }} border border-dashed mb-9 p-6">
        <!--begin::Icon-->
        <i class="fa-solid {{ $theme['icon'] }} fs-2tx text-{{ $theme['color'] }} me-4 mb-5 mb-sm-0 mt-1"></i>
        <!--end::Icon-->

        <!--begin::Wrapper-->
        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
            <!--begin::Content-->
            <div class="mb-3 mb-md-0 fw-semibold">
                <div class="d-flex align-items-center gap-3 mb-1">
                    <h3 class="text-gray-900 fw-bolder m-0">{{ $paymentRequest->requestNumber() }}</h3>
                    <span
                        class="badge badge-{{ $theme['color'] }} fs-7 fw-bold">{{ $paymentRequest->statusLabel() }}</span>
                </div>
                <div class="fs-6 text-gray-700 pe-7">
                    Track the review status of your payment. Source: <strong
                        class="text-gray-900">{{ $paymentRequest->gatewayLabel() }}</strong>
                    &bull; Submitted on
                    {{ optional($paymentRequest->submitted_at ?? $paymentRequest->created_at)->format('d M Y, H:i') }}
                </div>
            </div>
            <!--end::Content-->

            <!--begin::Action-->
            <div class="d-flex align-items-center flex-shrink-0 gap-3">
                <a href="{{ route('billing.plans') }}"
                    class="btn btn-bg-light btn-color-gray-700 btn-active-color-primary fw-bold">Plans</a>
                <a href="{{ route('billing.payments.history') }}"
                    class="btn btn-{{ $theme['color'] }} fw-bold">Payment History</a>
            </div>
            <!--end::Action-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!-- end::Hero Status Panel -->

    <div class="row g-6 g-xl-9">
        <!-- begin::Left Column (Details & Attachments) -->
        <div class="col-xl-7">
            <!-- begin::Details Card -->
            <div class="card card-flush h-md-100 mb-6 mb-xl-0">
                <div class="card-header pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Transaction Details</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Full overview of your submitted payment</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <!-- begin::Grid Info -->
                    <div class="row row-cols-1 row-cols-md-2 g-8 mb-10">
                        <!-- Plan -->
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px symbol-light-primary me-4">
                                    <span class="symbol-label"><i class="fa-solid fa-cube fs-2 text-primary"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold fs-7">Requested Plan</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-5">{{ $paymentRequest->plan?->name ?? 'Unknown plan' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px symbol-light-success me-4">
                                    <span class="symbol-label"><i
                                            class="fa-solid fa-money-bill-wave fs-2 text-success"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold fs-7">Amount Paid</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-5">{{ strtoupper($paymentRequest->currency) }}
                                        {{ number_format((float) $paymentRequest->amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Source -->
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px symbol-light-info me-4">
                                    <span class="symbol-label"><i
                                            class="fa-solid fa-building-columns fs-2 text-info"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold fs-7">Payment Source</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-5">{{ $paymentRequest->gatewayLabel() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Reference -->
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px symbol-light-warning me-4">
                                    <span class="symbol-label"><i
                                            class="fa-solid fa-hashtag fs-2 text-warning"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold fs-7">Transaction Reference</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-5">{{ $paymentRequest->transaction_reference }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end::Grid Info -->

                    <div class="separator separator-dashed my-8"></div>

                    <!-- begin::Attachments & Notes -->
                    <div class="d-flex flex-column gap-7">

                        <!-- Receipt -->
                        <div class="d-flex align-items-center justify-content-between bg-light p-4 rounded">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-file-invoice fs-1 text-primary me-4"></i>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-900 fw-bold">Payment Receipt</span>
                                    <span class="text-muted fs-7">Uploaded proof of transfer</span>
                                </div>
                            </div>
                            @if ($paymentRequest->hasReceipt())
                                <a href="{{ route('billing.payments.receipt', $paymentRequest) }}" target="_blank"
                                    class="btn btn-sm btn-light-primary fw-bold">View File</a>
                            @else
                                <span class="badge badge-light-danger">Missing</span>
                            @endif
                        </div>

                        <!-- Customer Note -->
                        <div>
                            <div class="text-muted fw-bold fs-7 mb-2"><i class="fa-solid fa-comment-dots me-1"></i> Your
                                Note</div>
                            <div class="bg-light rounded p-4 text-gray-800 fs-6">
                                {{ $paymentRequest->customer_note ?: 'No additional notes provided.' }}
                            </div>
                        </div>

                        <!-- Admin Note -->
                        @if ($paymentRequest->admin_note)
                            <div>
                                <div class="text-info fw-bold fs-7 mb-2"><i class="fa-solid fa-user-shield me-1"></i>
                                    Admin Feedback</div>
                                <div
                                    class="bg-light-info rounded p-4 text-gray-800 fs-6 border border-info border-dashed">
                                    {{ $paymentRequest->admin_note }}
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="text-muted fw-bold fs-7 mb-2"><i class="fa-solid fa-user-shield me-1"></i>
                                    Admin Feedback</div>
                                <div class="bg-light rounded p-4 text-gray-600 fs-6 fst-italic">
                                    Pending review by the billing team.
                                </div>
                            </div>
                        @endif

                    </div>
                    <!-- end::Attachments & Notes -->

                </div>
            </div>
            <!-- end::Details Card -->
        </div>
        <!-- end::Left Column -->

        <!-- begin::Right Column (Timeline & Actions) -->
        <div class="col-xl-5">
            <!-- begin::Timeline Card -->
            <div class="card card-flush mb-6">
                <div class="card-header pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Status Timeline</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">History of this request</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="timeline-label">
                        <!-- Created -->
                        <div class="timeline-item">
                            <div class="timeline-label fw-bold text-gray-800 fs-6 w-100px">
                                {{ optional($paymentRequest->created_at)->format('d M, H:i') }}</div>
                            <div class="timeline-badge ms-2">
                                <i class="fa-solid fa-file-export text-primary fs-5"></i>
                            </div>
                            <div class="timeline-content fw-semibold text-gray-700 ps-3">Payment request created.</div>
                        </div>

                        <!-- Reviewed -->
                        @if ($paymentRequest->reviewed_at)
                            <div class="timeline-item">
                                <div class="timeline-label fw-bold text-gray-800 fs-6 w-100px">
                                    {{ optional($paymentRequest->reviewed_at)->format('d M, H:i') }}</div>
                                <div class="timeline-badge ms-2">
                                    <i class="fa-solid fa-magnifying-glass text-warning fs-5"></i>
                                </div>
                                <div class="timeline-content fw-semibold text-gray-700 ps-3">
                                    Reviewed by <span
                                        class="text-gray-900 fw-bold">{{ $paymentRequest->reviewer?->name ?? 'billing team' }}</span>.
                                </div>
                            </div>
                        @endif

                        <!-- Approved -->
                        @if ($paymentRequest->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-label fw-bold text-gray-800 fs-6 w-100px">
                                    {{ optional($paymentRequest->approved_at)->format('d M, H:i') }}</div>
                                <div class="timeline-badge ms-2">
                                    <i class="fa-solid fa-check-circle text-success fs-5"></i>
                                </div>
                                <div class="timeline-content fw-semibold text-gray-700 ps-3">Payment approved & plan
                                    activated.</div>
                            </div>
                        @endif

                        <!-- Rejected -->
                        @if ($paymentRequest->rejected_at)
                            <div class="timeline-item">
                                <div class="timeline-label fw-bold text-gray-800 fs-6 w-100px">
                                    {{ optional($paymentRequest->rejected_at)->format('d M, H:i') }}</div>
                                <div class="timeline-badge ms-2">
                                    <i class="fa-solid fa-circle-xmark text-danger fs-5"></i>
                                </div>
                                <div class="timeline-content fw-semibold text-gray-700 ps-3">Payment rejected. Awaiting
                                    resubmission.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- end::Timeline Card -->

            <!-- begin::Invoice Action (If Approved) -->
            @if ($paymentRequest->invoice)
                <div class="separator my-6"></div>
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-bold text-gray-900">Invoice
                            {{ $paymentRequest->invoice->number ?: '#' . $paymentRequest->invoice->id }}</div>
                        <div class="text-muted fs-7">Your approved payment has a linked invoice record.</div>
                    </div>
                    <a href="{{ route('billing.invoices.download', $paymentRequest->invoice->id) }}"
                        class="btn btn-sm btn-light-primary">Download</a>
                </div>
            @endif
            <!-- end::Invoice Action -->

            <!-- begin::Resubmit Action (If Rejected) -->
            @if ($paymentRequest->isRejected())
                <div class="card border border-danger border-dashed mb-6">
                    <div class="card-header pt-5 border-0">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-danger"><i
                                    class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Action
                                Required</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Please correct the details and
                                resubmit</span>
                        </h3>
                    </div>
                    <div class="card-body pt-4">
                        <form method="POST" action="{{ route('billing.payments.resubmit', $paymentRequest) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $paymentRequest->plan_id }}">

                            <div class="mb-5">
                                <label for="transaction_reference"
                                    class="form-label fw-semibold text-gray-800">Updated Reference ID</label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                    <input type="text" id="transaction_reference" name="transaction_reference"
                                        class="form-control form-control-solid" placeholder="e.g. TXN-987654321"
                                        value="{{ old('transaction_reference', $paymentRequest->transaction_reference) }}">
                                </div>
                            </div>

                            <div class="mb-5">
                                <label for="receipt" class="form-label fw-semibold text-gray-800">New Receipt
                                    File</label>
                                <input type="file" id="receipt" name="receipt"
                                    class="form-control form-control-solid" accept=".jpg,.jpeg,.png,.pdf,.webp">
                                <div class="form-text mt-2">Allowed types: jpg, png, pdf, webp.</div>
                            </div>

                            <div class="mb-7">
                                <label for="customer_note" class="form-label fw-semibold text-gray-800">Additional
                                    Remarks</label>
                                <textarea id="customer_note" name="customer_note" rows="3" class="form-control form-control-solid"
                                    placeholder="Provide any clarifying details to the reviewer...">{{ old('customer_note', $paymentRequest->customer_note) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 fw-bold">
                                <i class="fa-solid fa-paper-plane me-2"></i> Resubmit for Review
                            </button>
                        </form>
                    </div>
                </div>
            @endif
            <!-- end::Resubmit Action -->

        </div>
        <!-- end::Right Column -->
    </div>

</x-default-layout>
