<x-default-layout>

    @section('title')
        Billing Invoices
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('billing.invoices.index') }}
    @endsection

    @include('pages.apps.billing.partials._alerts')
    @include('pages.apps.billing.partials._subnav')
    @include('pages.apps.billing.partials._summary-cards')

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-xl-row justify-content-between gap-6 align-items-center">
            <div>
                <h3 class="mb-2">Invoices and Activity</h3>
                <div class="text-muted mb-4">Keep billing history, invoice downloads, and payment activity together in a
                    dedicated <br/> archive view instead of leaving them on the dashboard.</div>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge badge-light-primary">{{ $recentInvoices->count() }} recent invoices loaded</span>
                    <span class="badge badge-light-info">{{ $transactions->count() }} activity records shown</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-start">
                <a href="{{ route('billing.payment-methods.index') }}" class="btn btn-light">Payment Methods</a>
                <a href="{{ route('billing.payments.history') }}" class="btn btn-light-primary">Payment Requests</a>
            </div>
        </div>
    </div>

    <div class="card mb-8">
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
                                    <span class="badge badge-light-{{ $invoice->paid ? 'success' : 'warning' }}">
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
                                <td colspan="4" class="text-center text-muted py-8">No invoices available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

</x-default-layout>
