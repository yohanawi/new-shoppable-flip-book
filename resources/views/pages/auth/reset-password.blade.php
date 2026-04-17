<x-auth-layout>

    <form class="form w-100" novalidate="novalidate" id="kt_new_password_form" data-kt-redirect-url="{{ route('login') }}"
        action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->token }}">
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">
                New Password
            </h1>

            <div class="text-gray-500 fw-semibold fs-6">
                Enter your new password.
            </div>
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

        <div class="d-flex flex-wrap justify-content-center pb-lg-0">
            <button type="button" id="kt_new_password_submit" class="btn btn-primary me-4">
                @include('partials/general/_button-indicator', ['label' => 'Submit'])
            </button>

            <a href="{{ route('login') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>

</x-auth-layout>
