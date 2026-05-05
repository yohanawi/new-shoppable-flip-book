<x-default-layout>

    @section('title')
        Account Settings
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('user-management.users.settings') }}
    @endsection

    @php
        $activeTab = request('tab', 'overview');
        $defaultAddress = $user->defaultAddress;
        $emailVerified = !is_null($user->email_verified_at);
    @endphp

    <div class="d-flex flex-column flex-lg-row gap-8">
        <div class="flex-column flex-lg-row-auto w-lg-350px w-xl-400px">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body pt-15">
                    <div class="d-flex flex-center flex-column mb-8">
                        <div class="symbol symbol-100px symbol-circle mb-5">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" />
                            @else
                                <div
                                    class="symbol-label fs-2 fw-bold {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $user->name) }}">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="fs-2hx fw-bold text-gray-900 mb-1 text-center">{{ $user->name }}</div>

                        <div class="fs-6 text-gray-600 mb-4">{{ $user->email }}</div>

                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-6">
                            @forelse ($user->roles as $role)
                                <span class="badge badge-light-primary fs-8 fw-bold px-4 py-2">
                                    {{ ucwords($role->name) }}
                                </span>
                            @empty
                                <span class="badge badge-light">No assigned role</span>
                            @endforelse
                        </div>

                        <div class="d-flex flex-wrap justify-content-center gap-3 w-100">
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 text-center">
                                <div class="fs-3 fw-bold text-gray-900">{{ number_format($supportSummary['total']) }}
                                </div>
                                <div class="fs-8 text-gray-600">Tickets</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 text-center">
                                <div class="fs-3 fw-bold text-gray-900">
                                    {{ number_format($billingSummary['invoices_count']) }}</div>
                                <div class="fs-8 text-gray-600">Invoices</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 text-center">
                                <div class="fs-3 fw-bold text-gray-900">
                                    {{ number_format($billingSummary['transactions_count']) }}</div>
                                <div class="fs-8 text-gray-600">Transactions</div>
                            </div>
                        </div>
                    </div>

                    <div class="separator separator-dashed mb-6"></div>

                    <div class="mb-6">
                        <div class="fw-bold text-gray-900 mb-2">Account ID</div>
                        <div class="text-gray-600">ID-{{ str_pad((string) $user->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>

                    <div class="mb-6">
                        <div class="fw-bold text-gray-900 mb-2">Email Status</div>
                        <div class="text-gray-600">
                            <span class="badge {{ $emailVerified ? 'badge-light-success' : 'badge-light-warning' }}">
                                {{ $emailVerified ? 'Verified' : 'Verification pending' }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="fw-bold text-gray-900 mb-2">Primary Address</div>
                        <div class="text-gray-600">
                            @if ($defaultAddress)
                                {{ $defaultAddress->address_line_1 }}<br>
                                @if ($defaultAddress->address_line_2)
                                    {{ $defaultAddress->address_line_2 }}<br>
                                @endif
                                {{ $defaultAddress->city }}, {{ $defaultAddress->state }}
                                {{ $defaultAddress->postal_code }}<br>
                                {{ $defaultAddress->country }}
                            @else
                                No address saved yet.
                            @endif
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="fw-bold text-gray-900 mb-2">Last Login</div>
                        <div class="text-gray-600">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i a') : 'No login recorded yet.' }}
                            @if ($user->last_login_ip)
                                <div class="fs-7 text-muted mt-1">IP {{ $user->last_login_ip }}</div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="fw-bold text-gray-900 mb-2">Member Since</div>
                        <div class="text-gray-600">{{ $user->created_at?->format('d M Y') ?? 'Unknown' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-lg-row-fluid">
            @if (session('settings_status'))
                <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                    <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-success">Settings saved</h4>
                        <span>{{ session('settings_status') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->has('settings'))
                <div class="alert alert-danger mb-8">{{ $errors->first('settings') }}</div>
            @endif

            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-8">
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'overview' ? 'active' : '' }}"
                        data-bs-toggle="tab" href="#account_settings_overview">Overview</a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'security' ? 'active' : '' }}"
                        data-bs-toggle="tab" href="#account_settings_security">Security</a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 py-5 {{ $activeTab === 'logs' ? 'active' : '' }}"
                        data-bs-toggle="tab" href="#account_settings_logs">Logs</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}"
                    id="account_settings_overview" role="tabpanel">
                    <div class="row g-6 g-xl-9 mb-8">
                        <div class="col-md-6 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="text-gray-600 fw-semibold mb-2">Open Tickets</div>
                                    <div class="fs-1 fw-bold text-gray-900">
                                        {{ number_format($supportSummary['open']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="text-gray-600 fw-semibold mb-2">Closed Tickets</div>
                                    <div class="fs-1 fw-bold text-gray-900">
                                        {{ number_format($supportSummary['closed']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="text-gray-600 fw-semibold mb-2">Payment Requests</div>
                                    <div class="fs-1 fw-bold text-gray-900">
                                        {{ number_format($billingSummary['payment_requests_count']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="text-gray-600 fw-semibold mb-2">Amount Paid</div>
                                    <div class="fs-1 fw-bold text-gray-900">
                                        {{ number_format($billingSummary['amount_paid'] / 100, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-8">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>Recent Support Tickets</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5">
                                    <thead
                                        class="border-bottom border-gray-200 fs-7 fw-bold text-gray-700 text-uppercase">
                                        <tr>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th class="text-end">Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @forelse ($recentSupportTickets as $ticket)
                                            <tr>
                                                <td>{{ $ticket->subject }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $ticket->category_name)) }}</td>
                                                <td>{{ ucfirst($ticket->priority) }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $ticket->status === 'closed' ? 'badge-light-success' : ($ticket->status === 'in_progress' ? 'badge-light-warning' : 'badge-light-primary') }}">
                                                        {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    {{ $ticket->updated_at?->diffForHumans() ?? 'n/a' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-10 text-muted">No support
                                                    tickets found for this account.</td>
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
                                        <h2>Recent Payment Requests</h2>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-5">
                                            <thead
                                                class="border-bottom border-gray-200 fs-7 fw-bold text-gray-700 text-uppercase">
                                                <tr>
                                                    <th>Request</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="fs-6 fw-semibold text-gray-600">
                                                @forelse ($recentPaymentRequests as $paymentRequest)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex flex-column">
                                                                <span
                                                                    class="text-gray-900">{{ $paymentRequest->requestNumber() }}</span>
                                                                <span
                                                                    class="fs-7 text-muted">{{ $paymentRequest->plan?->name ?? 'No plan linked' }}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge {{ $paymentRequest->status === 'approved' ? 'badge-light-success' : ($paymentRequest->status === 'rejected' ? 'badge-light-danger' : 'badge-light-warning') }}">
                                                                {{ $paymentRequest->statusLabel() }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            {{ strtoupper((string) $paymentRequest->currency) }}
                                                            {{ number_format((float) $paymentRequest->amount, 2) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-10 text-muted">No
                                                            payment requests recorded yet.</td>
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
                                        <table class="table align-middle table-row-dashed gy-5">
                                            <thead
                                                class="border-bottom border-gray-200 fs-7 fw-bold text-gray-700 text-uppercase">
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="fs-6 fw-semibold text-gray-600">
                                                @forelse ($recentTransactions as $transaction)
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex flex-column">
                                                                <span
                                                                    class="text-gray-900">{{ $transaction->description ?: ucfirst((string) $transaction->type) }}</span>
                                                                <span
                                                                    class="fs-7 text-muted">{{ $transaction->processed_at?->diffForHumans() ?? 'Pending' }}</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge {{ $transaction->status === 'succeeded' ? 'badge-light-success' : 'badge-light-danger' }}">
                                                                {{ strtoupper((string) $transaction->status) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            {{ strtoupper((string) $transaction->currency) }}
                                                            {{ number_format(((int) $transaction->amount) / 100, 2) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-10 text-muted">No
                                                            transactions recorded yet.</td>
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

                <div class="tab-pane fade {{ $activeTab === 'security' ? 'show active' : '' }}"
                    id="account_settings_security" role="tabpanel">
                    <div class="card mb-8">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title d-flex flex-column">
                                <h2 class="mb-1">Profile Details</h2>
                                <div class="fs-6 text-muted">Update the personal information stored for your account.
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <form method="POST"
                                action="{{ route('account.settings.update', ['tab' => 'security']) }}"
                                class="form">
                                @csrf
                                <input type="hidden" name="section" value="profile">

                                <div class="row g-6 mb-6">
                                    <div class="col-md-6">
                                        <label class="form-label required">Full Name</label>
                                        <input type="text" name="name" class="form-control form-control-solid"
                                            value="{{ old('name', $user->name) }}" autocomplete="name">
                                        @error('name')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <div
                                            class="form-control form-control-solid d-flex align-items-center gap-2 min-h-50px">
                                            @forelse ($user->roles as $role)
                                                <span
                                                    class="badge badge-light-primary">{{ ucwords($role->name) }}</span>
                                            @empty
                                                <span class="text-muted">No assigned role</span>
                                            @endforelse
                                        </div>
                                        <div class="fs-7 text-muted mt-2">Roles are managed by administrators and
                                            cannot be changed here.</div>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-8"></div>

                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label">Address Line 1</label>
                                        <input type="text" name="address_line_1"
                                            class="form-control form-control-solid"
                                            value="{{ old('address_line_1', $defaultAddress?->address_line_1) }}"
                                            autocomplete="address-line1">
                                        @error('address_line_1')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Address Line 2</label>
                                        <input type="text" name="address_line_2"
                                            class="form-control form-control-solid"
                                            value="{{ old('address_line_2', $defaultAddress?->address_line_2) }}"
                                            autocomplete="address-line2">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control form-control-solid"
                                            value="{{ old('city', $defaultAddress?->city) }}"
                                            autocomplete="address-level2">
                                        @error('city')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State / Province</label>
                                        <input type="text" name="state" class="form-control form-control-solid"
                                            value="{{ old('state', $defaultAddress?->state) }}"
                                            autocomplete="address-level1">
                                        @error('state')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" name="postal_code"
                                            class="form-control form-control-solid"
                                            value="{{ old('postal_code', $defaultAddress?->postal_code) }}"
                                            autocomplete="postal-code">
                                        @error('postal_code')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" class="form-control form-control-solid"
                                            value="{{ old('country', $defaultAddress?->country) }}"
                                            autocomplete="country-name">
                                        @error('country')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-10">
                                    <button type="submit" class="btn btn-primary">Save Profile Details</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row g-6 g-xl-9">
                        <div class="col-xl-6">
                            <div class="card h-100">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title d-flex flex-column">
                                        <h2 class="mb-1">Email Address</h2>
                                        <div class="fs-6 text-muted">Keep your login email current and unique.</div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <form method="POST"
                                        action="{{ route('account.settings.update', ['tab' => 'security']) }}"
                                        class="form">
                                        @csrf
                                        <input type="hidden" name="section" value="email">

                                        <div class="mb-8">
                                            <div class="fs-6 fw-semibold text-gray-600 mb-2">Current email</div>
                                            <div class="fs-5 text-gray-900">{{ $user->email }}</div>
                                        </div>

                                        <div class="mb-8">
                                            <label class="form-label required">New Email</label>
                                            <input type="email" name="email"
                                                class="form-control form-control-solid"
                                                value="{{ old('email', $user->email) }}" autocomplete="email">
                                            @error('email')
                                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Update Email</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="card h-100">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title d-flex flex-column">
                                        <h2 class="mb-1">Password</h2>
                                        <div class="fs-6 text-muted">Use your current password to confirm this change.
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <form method="POST"
                                        action="{{ route('account.settings.update', ['tab' => 'security']) }}"
                                        class="form">
                                        @csrf
                                        <input type="hidden" name="section" value="password">

                                        <div class="mb-6">
                                            <label class="form-label required">Current Password</label>
                                            <input type="password" name="current_password"
                                                class="form-control form-control-solid"
                                                autocomplete="current-password">
                                            @error('current_password')
                                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-6">
                                            <label class="form-label required">New Password</label>
                                            <input type="password" name="password"
                                                class="form-control form-control-solid" autocomplete="new-password">
                                            @error('password')
                                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-8">
                                            <label class="form-label required">Confirm New Password</label>
                                            <input type="password" name="password_confirmation"
                                                class="form-control form-control-solid" autocomplete="new-password">
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">Update Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'logs' ? 'show active' : '' }}"
                    id="account_settings_logs" role="tabpanel">
                    <div class="card mb-8">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title d-flex flex-column">
                                <h2 class="mb-1">Account Access</h2>
                                <div class="fs-6 text-muted">This app currently stores the latest successful sign-in,
                                    not a full multi-session history.</div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-6">
                                <div class="col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded p-5 h-100">
                                        <div class="fs-7 text-muted mb-2">Last Login</div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i a') : 'Not available' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded p-5 h-100">
                                        <div class="fs-7 text-muted mb-2">Last Login IP</div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $user->last_login_ip ?: 'Not available' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded p-5 h-100">
                                        <div class="fs-7 text-muted mb-2">Email Verification</div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $emailVerified ? 'Verified' : 'Pending' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded p-5 h-100">
                                        <div class="fs-7 text-muted mb-2">Account Created</div>
                                        <div class="fw-bold text-gray-900">
                                            {{ $user->created_at?->format('d M Y') ?? 'Unknown' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-8">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>Recent Account Activity</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5">
                                    <thead
                                        class="border-bottom border-gray-200 fs-7 fw-bold text-gray-700 text-uppercase">
                                        <tr>
                                            <th>Event</th>
                                            <th>Context</th>
                                            <th class="text-end">When</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @forelse ($activityLog as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex align-items-center gap-3 mb-1">
                                                            <span
                                                                class="badge {{ $item['badge_class'] }}">{{ $item['headline'] }}</span>
                                                        </div>
                                                        <span>{{ $item['details'] }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $item['context'] }}</td>
                                                <td class="text-end">
                                                    <div class="d-flex flex-column align-items-end">
                                                        <span>{{ $item['timestamp']->format('d M Y, h:i a') }}</span>
                                                        <span
                                                            class="fs-7 text-muted">{{ $item['timestamp']->diffForHumans() }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-10 text-muted">No activity
                                                    has been recorded for this account yet.</td>
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
                                <h2>Recent Invoices</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-5">
                                    <thead
                                        class="border-bottom border-gray-200 fs-7 fw-bold text-gray-700 text-uppercase">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th class="text-end">Amount Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @forelse ($recentInvoices as $invoice)
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="text-gray-900">{{ $invoice->number ?: 'Invoice #' . $invoice->id }}</span>
                                                        <span
                                                            class="fs-7 text-muted">{{ $invoice->created_at?->diffForHumans() ?? 'n/a' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $invoice->status === 'paid' ? 'badge-light-success' : 'badge-light-info' }}">
                                                        {{ strtoupper((string) $invoice->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    {{ strtoupper((string) $invoice->currency) }}
                                                    {{ number_format(((int) $invoice->amount_paid) / 100, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-10 text-muted">No invoices
                                                    recorded yet.</td>
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
