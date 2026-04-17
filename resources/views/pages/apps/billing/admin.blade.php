<x-default-layout>

    @section('title')
        Billing Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('admin.billing.index') }}
    @endsection

    @if (session('success'))
        <div class="alert alert-success mb-8">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-bold mb-2">Admin billing action could not be completed.</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">30 Day Revenue</div>
                    <div class="fs-2hx fw-bold text-gray-900">USD
                        {{ number_format($metrics['monthly_revenue'] / 100, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Active Subscriptions</div>
                    <div class="fs-2hx fw-bold text-gray-900">{{ number_format($metrics['active_subscriptions']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Churned in 30 Days</div>
                    <div class="fs-2hx fw-bold text-gray-900">{{ number_format($metrics['churned_subscriptions']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-semibold fs-7 mb-2">Failed Payments</div>
                    <div class="fs-2hx fw-bold text-gray-900">{{ number_format($metrics['failed_payments']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Create Plan</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('admin.billing.plans.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control form-control-solid"></textarea>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" min="0" step="0.01"
                                    class="form-control form-control-solid" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" maxlength="3"
                                    class="form-control form-control-solid" value="usd" required>
                            </div>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Interval</label>
                                <select name="interval" class="form-select form-select-solid">
                                    <option value="month">Month</option>
                                    <option value="year">Year</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Trial Days</label>
                                <input type="number" name="trial_days" min="0"
                                    class="form-control form-control-solid">
                            </div>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Flipbook Limit</label>
                                <input type="number" name="limits[flipbooks]" min="1"
                                    class="form-control form-control-solid">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Storage MB</label>
                                <input type="number" name="limits[storage_mb]" min="1"
                                    class="form-control form-control-solid">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Stripe Price ID</label>
                            <input type="text" name="stripe_price_id" class="form-control form-control-solid">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Stripe Product ID</label>
                            <input type="text" name="stripe_product_id" class="form-control form-control-solid">
                        </div>
                        <div class="d-flex gap-6 mb-6">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="features[analytics]"
                                    value="1">
                                <span class="form-check-label">Analytics</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="features[branding]"
                                    value="1">
                                <span class="form-check-label">Branding</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Plan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Plan Management</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Plan</th>
                                    <th>Limits</th>
                                    <th>Stripe</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @foreach ($plans as $plan)
                                    <tr>
                                        <td class="min-w-250px">
                                            <form method="POST"
                                                action="{{ route('admin.billing.plans.update', $plan) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="mb-3">
                                                    <input type="text" name="name"
                                                        class="form-control form-control-solid"
                                                        value="{{ $plan->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <input type="text" name="slug"
                                                        class="form-control form-control-solid"
                                                        value="{{ $plan->slug }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <textarea name="description" rows="2" class="form-control form-control-solid">{{ $plan->description }}</textarea>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <input type="number" name="price" min="0"
                                                            step="0.01" class="form-control form-control-solid"
                                                            value="{{ $plan->price }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" name="currency" maxlength="3"
                                                            class="form-control form-control-solid"
                                                            value="{{ $plan->currency }}" required>
                                                    </div>
                                                </div>
                                        </td>
                                        <td class="min-w-250px">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <input type="number" name="limits[flipbooks]" min="1"
                                                        class="form-control form-control-solid"
                                                        value="{{ data_get($plan->limits, 'flipbooks') }}"
                                                        placeholder="Flipbooks">
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="limits[storage_mb]" min="1"
                                                        class="form-control form-control-solid"
                                                        value="{{ data_get($plan->limits, 'storage_mb') }}"
                                                        placeholder="Storage MB">
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-4">
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="features[analytics]" value="1"
                                                        {{ data_get($plan->features, 'analytics') ? 'checked' : '' }}>
                                                    <span class="form-check-label">Analytics</span>
                                                </label>
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="features[branding]" value="1"
                                                        {{ data_get($plan->features, 'branding') ? 'checked' : '' }}>
                                                    <span class="form-check-label">Branding</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="min-w-200px">
                                            <div class="mb-3">
                                                <input type="text" name="stripe_price_id"
                                                    class="form-control form-control-solid"
                                                    value="{{ $plan->stripe_price_id }}"
                                                    placeholder="Stripe price ID">
                                            </div>
                                            <input type="text" name="stripe_product_id"
                                                class="form-control form-control-solid"
                                                value="{{ $plan->stripe_product_id }}"
                                                placeholder="Stripe product ID">
                                            <div class="row g-3 mt-1">
                                                <div class="col-md-6">
                                                    <select name="interval" class="form-select form-select-solid">
                                                        <option value="month"
                                                            {{ $plan->interval === 'month' ? 'selected' : '' }}>Month
                                                        </option>
                                                        <option value="year"
                                                            {{ $plan->interval === 'year' ? 'selected' : '' }}>Year
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" name="trial_days" min="0"
                                                        class="form-control form-control-solid"
                                                        value="{{ $plan->trial_days }}" placeholder="Trial days">
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <label class="form-check form-check-custom form-check-solid mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                    value="1" {{ $plan->is_active ? 'checked' : '' }}>
                                                <span class="form-check-label">Active</span>
                                            </label>
                                            <input type="number" name="sort_order" min="0"
                                                class="form-control form-control-solid"
                                                value="{{ $plan->sort_order }}" placeholder="Sort order">
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2 mb-3">
                                                <button type="submit"
                                                    class="btn btn-sm btn-light-primary">Save</button>
                                            </div>
                                            </form>
                                            <form method="POST"
                                                action="{{ route('admin.billing.plans.destroy', $plan) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-light-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-8">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Subscription Management</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Ends</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-700">
                        @forelse ($subscriptions as $subscription)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span
                                            class="text-gray-900 fw-bold">{{ $subscription->owner?->name ?? 'Unknown user' }}</span>
                                        <span class="text-muted fs-7">{{ $subscription->owner?->email }}</span>
                                    </div>
                                </td>
                                <td>{{ $subscription->plan?->name ?? 'Unmapped Stripe plan' }}</td>
                                <td><span
                                        class="badge badge-light">{{ ucfirst(str_replace('_', ' ', $subscription->stripe_status)) }}</span>
                                </td>
                                <td>{{ optional($subscription->ends_at)->format('d M Y') ?? 'Active' }}</td>
                                <td class="text-end min-w-300px">
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="POST"
                                            action="{{ route('admin.billing.subscriptions.swap', $subscription) }}"
                                            class="d-flex gap-2">
                                            @csrf
                                            <select name="plan_id"
                                                class="form-select form-select-solid form-select-sm w-175px">
                                                @foreach ($plans as $plan)
                                                    <option value="{{ $plan->id }}"
                                                        {{ (int) $subscription->plan_id === (int) $plan->id ? 'selected' : '' }}>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-light-primary">Swap</button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('admin.billing.subscriptions.cancel', $subscription) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-danger">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-8">No Stripe subscriptions synced
                                    yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-6 g-xl-9">
        <div class="col-xl-6">
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
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th class="text-end">PDF</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->user?->email }}</td>
                                        <td><span class="badge badge-light">{{ ucfirst($invoice->status) }}</span>
                                        </td>
                                        <td>{{ strtoupper($invoice->currency) }}
                                            {{ number_format(($invoice->amount_paid ?: $invoice->amount_due) / 100, 2) }}
                                        </td>
                                        <td class="text-end">
                                            @if ($invoice->invoice_pdf_url)
                                                <a href="{{ $invoice->invoice_pdf_url }}" target="_blank"
                                                    class="btn btn-sm btn-light-primary">Open</a>
                                            @else
                                                <span class="text-muted fs-7">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-8">No invoice records yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Recent Transactions</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-700">
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->user?->email }}</td>
                                        <td>{{ ucfirst($transaction->type) }}</td>
                                        <td><span class="badge badge-light">{{ ucfirst($transaction->status) }}</span>
                                        </td>
                                        <td>{{ strtoupper($transaction->currency) }}
                                            {{ number_format($transaction->amount / 100, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-8">No payment records yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
