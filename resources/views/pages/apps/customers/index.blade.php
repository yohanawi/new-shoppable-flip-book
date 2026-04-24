<x-default-layout>

    @section('title')
        Customers
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('admin.customers.index') }}
    @endsection

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Customers</div>
                    <div class="fs-2hx fw-bold">{{ number_format($stats['customers_count']) }}</div>
                    <div class="text-gray-500 mt-2">Filtered results in the admin directory.</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Verified</div>
                    <div class="fs-2hx fw-bold">{{ number_format($stats['verified_count']) }}</div>
                    <div class="text-gray-500 mt-2">Customers with confirmed email addresses.</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Active 30 Days</div>
                    <div class="fs-2hx fw-bold">{{ number_format($stats['active_count']) }}</div>
                    <div class="text-gray-500 mt-2">Customers with a recent recorded sign-in.</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-7 text-uppercase mb-2">Uploaded PDFs</div>
                    <div class="fs-2hx fw-bold">{{ number_format($stats['catalogs_count']) }}</div>
                    <div class="text-gray-500 mt-2">Catalog assets owned by visible customers.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6 flex-wrap gap-4">
            <div>
                <h2 class="mb-1">Customer Directory</h2>
                <div class="text-muted">Admin-only workspace for customer records, analytics, activity, and uploaded
                    PDFs.</div>
            </div>
            <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5 top-50 translate-middle-y') !!}
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search by name or email" />
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                @if ($filters['search'] !== '')
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Customer</th>
                            <th>Uploaded PDFs</th>
                            <th>Support Tickets</th>
                            <th>Invoices</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @forelse ($customers as $customer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-45px symbol-circle">
                                            @if ($customer->profile_photo_url)
                                                <img src="{{ $customer->profile_photo_url }}"
                                                    alt="{{ $customer->name }}" />
                                            @else
                                                <div class="symbol-label fs-6 fw-bold bg-light-primary text-primary">
                                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('admin.customers.show', $customer) }}"
                                                class="text-gray-800 text-hover-primary fs-6 fw-bold">
                                                {{ $customer->name }}
                                            </a>
                                            <span class="text-muted fs-7">{{ $customer->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ number_format($customer->catalog_pdfs_count) }}</td>
                                <td>{{ number_format($customer->support_tickets_count) }}</td>
                                <td>{{ number_format($customer->billing_invoices_count) }}</td>
                                <td>{{ $customer->last_login_at?->diffForHumans() ?? 'Never recorded' }}</td>
                                <td>{{ $customer->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                        class="btn btn-sm btn-light-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-10">No customers matched the current
                                    filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="d-flex justify-content-end mt-6">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>

</x-default-layout>
