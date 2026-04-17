<x-auth-layout>
    <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" data-kt-redirect-url="{{ route('login') }}"
        action="{{ route('register') }}" method="POST">
        @csrf

        @if (session('status'))
            <div class="alert alert-info mb-8">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <div class="fw-bold mb-2">Unable to create account.</div>
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">
                Sign Up
            </h1>

            <div class="text-gray-500 fw-semibold fs-6">
                Sign up to manage your Flipbook Shop & Products
            </div>
        </div>

        <div class="row g-3 mb-9"></div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Name" name="name" autocomplete="off"
                class="form-control bg-transparent @error('name') is-invalid @enderror" value="{{ old('name') }}" />
        </div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" autocomplete="off"
                class="form-control bg-transparent @error('email') is-invalid @enderror" value="{{ old('email') }}" />
        </div>

        <div class="fv-row mb-8" data-kt-password-meter="true">
            <div class="mb-1">
                <div class="position-relative mb-3">
                    <input class="form-control bg-transparent" type="password" placeholder="Password" name="password"
                        autocomplete="off" />

                    <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                        data-kt-password-meter-control="visibility">
                        <i class="bi bi-eye-slash fs-2"></i>
                        <i class="bi bi-eye fs-2 d-none"></i>
                    </span>
                </div>

                <div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
                    <div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
                </div>
            </div>

            <div class="text-muted">
                Use 8 or more characters with a mix of letters, numbers & symbols.
            </div>
        </div>

        <div class="fv-row mb-8">
            <input placeholder="Repeat Password" name="password_confirmation" type="password" autocomplete="off"
                class="form-control bg-transparent" />
        </div>

        <div class="fv-row mb-10">
            <div class="form-check form-check-custom form-check-solid form-check-inline">
                <input class="form-check-input" type="checkbox" name="toc" value="1" />

                <label class="form-check-label fw-semibold text-gray-700 fs-6">
                    I Agree & <a href="#" class="ms-1 link-primary">Terms and conditions</a>.
                </label>
            </div>
        </div>

        <div class="d-grid mb-10">
            <button type="submit" id="kt_sign_up_submit" class="btn btn-primary">
                @include('partials/general/_button-indicator', ['label' => 'Sign Up'])
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            Already have an Account?

            <a href="{{ route('login') }}" class="link-primary fw-semibold">
                Sign in
            </a>
        </div>
    </form>

</x-auth-layout>
