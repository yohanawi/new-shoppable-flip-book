<x-default-layout>

    @section('title')
        Customer Workspace
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('admin.customers.show', $customer) }}
    @endsection

    <div class="d-flex flex-column flex-xl-row gap-8">
        <div class="flex-column flex-xl-row-auto w-xl-350px">
            <div class="card mb-8">
                <div class="card-body">
                    <div class="d-flex flex-center flex-column py-6">
                        <div class="symbol symbol-100px symbol-circle mb-5">
                            @if ($customer->profile_photo_url)
                                <img src="{{ $customer->profile_photo_url }}" alt="{{ $customer->name }}" />
                            @else
                                <div class="symbol-label fs-2 fw-bold bg-light-primary text-primary">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="fs-3 fw-bold text-gray-900">{{ $customer->name }}</div>
                        <div class="text-muted mb-4">{{ $customer->email }}</div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                            <span class="badge badge-light-primary">Customer</span>
                            @if ($customer->email_verified_at)
                                <span class="badge badge-light-success">Verified</span>
                            @else
                                <span class="badge badge-light-warning">Unverified</span>
                            @endif
                            @if ($customer->stripe_id)
                                <span class="badge badge-light-info">Stripe linked</span>
                            @endif
                        </div>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">Back to
                            Customers</a>
                    </div>
                    <div class="separator my-5"></div>
                    <div class="fs-6">
                        <div class="fw-bold mt-4">Joined</div>
                        <div class="text-gray-600">{{ $customer->created_at?->format('d M Y, h:i a') ?? 'Unknown' }}
                        </div>

                        <div class="fw-bold mt-4">Last Login</div>
                        <div class="text-gray-600">
                            {{ $customer->last_login_at?->format('d M Y, h:i a') ?? 'Never recorded' }}</div>

                        <div class="fw-bold mt-4">Last Login IP</div>
                        <div class="text-gray-600">{{ $customer->last_login_ip ?? 'Unavailable' }}</div>

                        <div class="fw-bold mt-4">Default Address</div>
                        <div class="text-gray-600">
                            @if ($customer->default_address)
                                {{ $customer->default_address->address_line_1 ?? ($customer->default_address->address ?? 'Address available') }}
                            @else
                                No address saved.
                            @endif
                        </div>

                        <div class="fw-bold mt-4">Stripe Customer ID</div>
                        <div class="text-gray-600">{{ $customer->stripe_id ?? 'Not linked' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-row-fluid">
            <div class="card mb-8">
                <div class="card-body py-7">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
                        <div>
                            <h2 class="mb-2">Customer Workspace</h2>
                            <div class="text-muted">View customer analytics, activity, billing, support, and uploaded
                                PDFs in one place.</div>
                        </div>
                        <div class="badge badge-light-danger fs-7">Admin only</div>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-8 mb-8">
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Uploaded PDFs</div>
                            <div class="fs-2hx fw-bold">{{ number_format($catalogSummary['uploaded_count']) }}</div>
                            <div class="text-gray-500 mt-2">{{ $catalogSummary['storage_human'] }} of catalog storage.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Catalog Views</div>
                            <div class="fs-2hx fw-bold">{{ number_format($analyticsSummary['views_count']) }}</div>
                            <div class="text-gray-500 mt-2">{{ number_format($analyticsSummary['readers_count']) }}
                                unique readers.</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Support Tickets</div>
                            <div class="fs-2hx fw-bold">{{ number_format($supportSummary['total']) }}</div>
                            <div class="text-gray-500 mt-2">{{ number_format($supportSummary['open']) }} open,
                                {{ number_format($supportSummary['closed']) }} closed.</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Paid Amount</div>
                            <div class="fs-2hx fw-bold">${{ number_format($billingSummary['amount_paid'] / 100, 2) }}
                            </div>
                            <div class="text-gray-500 mt-2">{{ number_format($billingSummary['invoices_count']) }}
                                invoices on record.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-8">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">Uploaded PDFs</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Title</th>
                                    <th>Workflow</th>
                                    <th>Visibility</th>
                                    <th>Uploaded</th>
                                    <th>Size</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse ($catalogPdfs as $pdf)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-gray-800 fw-bold">{{ $pdf->title }}</span>
                                                <span class="text-muted fs-7">{{ $pdf->original_filename }}</span>
                                            </div>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $pdf->template_type)) }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'badge-light-success' : 'badge-light-dark' }}">
                                                {{ ucfirst($pdf->visibility) }}
                                            </span>
                                        </td>
                                        <td>{{ $pdf->created_at?->format('d M Y, h:i a') }}</td>
                                        <td>{{ number_format(($pdf->size ?? 0) / 1024, 1) }} KB</td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                                                class="btn btn-sm btn-light-primary">Open</a>
                                            <a href="{{ route('catalog.pdfs.share', $pdf) }}"
                                                class="btn btn-sm btn-light ms-2">Share</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-10">This customer has not
                                            uploaded any PDFs yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-8">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">Analytics by PDF</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>PDF</th>
                                    <th>Views</th>
                                    <th>Readers</th>
                                    <th>Reading Time</th>
                                    <th>Hotspot Clicks</th>
                                    <th>Last Viewed</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse ($analyticsBooks as $book)
                                    <tr>
                                        <td>{{ $book['pdf']->title }}</td>
                                        <td>{{ number_format($book['views_count']) }}</td>
                                        <td>{{ number_format($book['readers_count']) }}</td>
                                        <td>{{ $book['time_spent_human'] }}</td>
                                        <td>{{ number_format($book['slice_click_count']) }}</td>
                                        <td>{{ $book['last_viewed_at']?->diffForHumans() ?? 'No activity' }}</td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ $book['manage_url'] }}"
                                                class="btn btn-sm btn-light-primary">Manage</a>
                                            <a href="{{ $book['share_url'] }}"
                                                class="btn btn-sm btn-light ms-2">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-10">No analytics have been
                                            recorded for this customer yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-8 mb-8">
                <div class="col-12 col-xxl-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold m-0">Activity Log</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            @forelse ($activityLog as $item)
                                <div
                                    class="d-flex gap-4 py-4 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">
                                    <div class="pt-1">
                                        <span
                                            class="badge {{ $item['badge_class'] }}">{{ $item['timestamp']?->format('d M') ?? 'Log' }}</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                            <span class="fw-bold text-gray-900">{{ $item['headline'] }}</span>
                                            <span
                                                class="text-muted fs-7">{{ $item['timestamp']?->diffForHumans() ?? 'Unknown time' }}</span>
                                        </div>
                                        <div class="text-gray-700">{{ $item['details'] }}</div>
                                        <div class="text-muted fs-7 mt-1">{{ $item['context'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-10">No activity has been recorded for this
                                    customer yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xxl-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold m-0">Support Tickets</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @forelse ($supportTickets as $ticket)
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="text-gray-800 fw-bold">{{ $ticket->subject }}</span>
                                                        <span
                                                            class="text-muted fs-7">{{ $ticket->category_name }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ ucfirst($ticket->status) }}</td>
                                                <td>{{ ucfirst($ticket->priority) }}</td>
                                                <td>{{ $ticket->updated_at?->diffForHumans() }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-10">No support
                                                    tickets found for this customer.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 g-xl-8">
                <div class="col-12 col-xxl-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold m-0">Invoices</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Amount Paid</th>
                                            <th>Paid At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @forelse ($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->number ?: $invoice->stripe_invoice_id ?: 'Invoice #' . $invoice->id }}
                                                </td>
                                                <td>{{ ucfirst($invoice->status ?? 'unknown') }}</td>
                                                <td>${{ number_format(($invoice->amount_paid ?? 0) / 100, 2) }}</td>
                                                <td>{{ $invoice->paid_at?->format('d M Y, h:i a') ?? 'Pending' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-10">No invoices
                                                    recorded for this customer.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xxl-6">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold m-0">Transactions</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600">
                                        @forelse ($transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->description ?: ($transaction->invoice?->number ?: 'Transaction #' . $transaction->id) }}
                                                </td>
                                                <td>{{ ucfirst($transaction->status ?? 'unknown') }}</td>
                                                <td>${{ number_format(($transaction->amount ?? 0) / 100, 2) }}</td>
                                                <td>{{ $transaction->processed_at?->format('d M Y, h:i a') ?? 'Pending' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-10">No
                                                    transactions recorded for this customer.</td>
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
    </div>

</x-default-layout>
