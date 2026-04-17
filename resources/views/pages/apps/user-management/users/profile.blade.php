<x-default-layout>

    @section('title')
        My Profile
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('user-management.users.profile') }}
    @endsection

    <div class="d-flex flex-column flex-lg-row">
        <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-350px mb-10">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body">
                    <div class="d-flex flex-center flex-column py-5">
                        <div class="symbol symbol-100px symbol-circle mb-7">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="image" />
                            @else
                                <div
                                    class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $user->name) }}">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-3">
                            {{ $user->name }}
                        </a>
                        <div class="mb-3">
                            <span class="badge badge-light-info">{{ $user->email }}</span>
                        </div>
                        <div class="mb-9">
                            @foreach ($user->roles as $role)
                                <div class="badge badge-lg badge-light-primary d-inline">
                                    {{ ucwords($role->name) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="separator"></div>
                    <div class="pb-5 fs-6">
                        <div class="fw-bold mt-5">Account ID</div>
                        <div class="text-gray-600">ID-{{ $user->id }}</div>
                        <div class="fw-bold mt-5">Email</div>
                        <div class="text-gray-600">
                            <a href="mailto:{{ $user->email }}"
                                class="text-gray-600 text-hover-primary">{{ $user->email }}</a>
                        </div>
                        @if ($user->defaultAddress)
                            <div class="fw-bold mt-5">Address</div>
                            <div class="text-gray-600">
                                {{ $user->defaultAddress->address_line_1 ?? '' }}<br>
                                {{ $user->defaultAddress->city ?? '' }}
                                {{ $user->defaultAddress->postal_code ?? '' }}<br>
                                {{ $user->defaultAddress->country ?? '' }}
                            </div>
                        @endif
                        <div class="fw-bold mt-5">Last Login</div>
                        <div class="text-gray-600">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i a') : 'Never' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-lg-row-fluid ms-lg-15">
            <div class="card">
                <div class="card-toolbar d-flex justify-content-end p-4">
                    <a href="{{ route('account.settings') }}" class="btn btn-light-primary btn-sm px-5 fw-bold">
                        <i class="ki-duotone ki-pencil fs-4 me-2"></i> Edit Profile
                    </a>
                </div>
                <div class="card-header">
                    <h3 class="card-title">Profile Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Full Name</label>
                        <div class="col-lg-8">
                            <span class="fw-bold fs-6 text-gray-800">{{ $user->name }}</span>
                        </div>
                    </div>
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Email</label>
                        <div class="col-lg-8">
                            <span class="fw-bold fs-6 text-gray-800">{{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Roles</label>
                        <div class="col-lg-8">
                            @foreach ($user->roles as $role)
                                <span class="badge badge-light-primary">{{ ucwords($role->name) }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-semibold text-muted">Last Login</label>
                        <div class="col-lg-8">
                            <span
                                class="fw-bold fs-6 text-gray-800">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i a') : 'Never' }}</span>
                        </div>
                    </div>
                    <!-- Add more profile details here as needed -->
                </div>
            </div>
        </div>
    </div>

</x-default-layout>
