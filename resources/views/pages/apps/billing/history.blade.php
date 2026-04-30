<x-default-layout>

    @section('title')
        Payment History
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.payments.history') }}
    @endsection

    @php
        $billingActiveSection = 'payment-requests';
    @endphp

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div>
                <h3 class="mb-2">Payment Request History</h3>
                <div class="text-muted">Review submitted payment requests, approval results, and linked invoices in one
                    timeline.</div>
            </div>
            <div class="d-flex gap-3">
                <a href="{{ route('billing.payments.create') }}" class="btn btn-primary">New Payment Request</a>
                <a href="{{ route('billing.plans') }}" class="btn btn-light">Plans</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($paymentRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Request</th>
                                <th>Plan</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @foreach ($paymentRequests as $paymentRequest)
                                @php
                                    $statusTone = match ($paymentRequest->status) {
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
                                            class="badge badge-light-{{ $statusTone }}">{{ $paymentRequest->statusLabel() }}</span>
                                    </td>
                                    <td>{{ strtoupper($paymentRequest->currency) }}
                                        {{ number_format((float) $paymentRequest->amount, 2) }}</td>
                                    <td>{{ optional($paymentRequest->submitted_at ?? $paymentRequest->created_at)->format('d M Y H:i') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('billing.payments.show', $paymentRequest) }}"
                                            class="btn btn-sm btn-light-primary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($paymentRequests->hasPages())
                    <div class="pt-8">
                        {{ $paymentRequests->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-20">
                    <h3 class="text-muted">No payment requests yet</h3>
                    <p class="text-muted fs-5 mb-5">When you submit a manual payment, it will appear here with its
                        review status.</p>
                    <a href="{{ route('billing.payments.create') }}" class="btn btn-primary">Submit Payment</a>
                </div>
            @endif
        </div>
    </div>

</x-default-layout>
