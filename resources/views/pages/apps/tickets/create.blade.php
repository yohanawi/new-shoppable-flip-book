<x-default-layout>

    @section('title')
        Create Support Ticket
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.create') }}
    @endsection

    {{-- Content --}}
    <div id="kt_app_content_container">

        {{-- Info Alert --}}
        <div
            class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row p-5 mb-10">
            <i class="ki-duotone ki-notification-bing fs-2hx text-primary me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1">Need Help?</h5>
                <span class="text-gray-700">Describe your issue in detail below and our support team will get back
                    to you as soon as possible.</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-duotone ki-cross fs-1 text-primary">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </button>
        </div>

        <div class="row g-7">
            {{-- Main Form --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Ticket Details</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Fill in the information below</span>
                        </h3>
                    </div>

                    <div class="card-body pt-0">
                        <form action="{{ route('tickets.store') }}" method="POST" id="kt_ticket_form">
                            @csrf

                            {{-- Subject --}}
                            <div class="mb-8">
                                <label class="form-label required fs-6 fw-semibold text-gray-800">Subject</label>
                                <input type="text" name="subject"
                                    class="form-control form-control-solid @error('subject') is-invalid @enderror"
                                    value="{{ old('subject') }}" required placeholder="Brief description of your issue">
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Category & Priority --}}
                            <div class="row g-7 mb-8">
                                <div class="col-md-6">
                                    <label class="form-label required fs-6 fw-semibold text-gray-800">Category</label>
                                    <select name="category"
                                        class="form-select form-select-solid @error('category') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true" required>
                                        <option value="">Select Category</option>
                                        <option value="technical"
                                            {{ old('category') === 'technical' ? 'selected' : '' }}>
                                            <i class="ki-duotone ki-setting-2"></i> Technical Support
                                        </option>
                                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>
                                            Billing & Account
                                        </option>
                                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>
                                            General Inquiry
                                        </option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required fs-6 fw-semibold text-gray-800">Priority</label>
                                    <select name="priority"
                                        class="form-select form-select-solid @error('priority') is-invalid @enderror"
                                        data-hide-search="true" data-control="select2" required>
                                        <option value="">Select Priority</option>
                                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>
                                            Low - General question
                                        </option>
                                        <option value="medium"
                                            {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>
                                            Medium - Need assistance
                                        </option>
                                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>
                                            High - Urgent issue
                                        </option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Message --}}
                            <div class="mb-10">
                                <label class="form-label required fs-6 fw-semibold text-gray-800">Message</label>
                                <textarea name="message" class="form-control form-control-solid @error('message') is-invalid @enderror" rows="10"
                                    required placeholder="Describe your issue in detail...">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    Please provide as much detail as possible to help us resolve your issue quickly.
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('tickets.index') }}"
                                    class="btn btn-light btn-active-light-primary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ki-duotone ki-send fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Submit Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Quick Tips Card --}}
                <div class="card shadow-sm mb-7">
                    <div class="card-body">
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-light-success">
                                        <i class="ki-duotone ki-information-5 fs-2 text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="text-gray-900 fw-bold fs-5 mb-0">Quick Tips</h3>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="mb-4">
                            <div class="d-flex align-items-start mb-4">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">
                                    Be specific about your issue
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">
                                    Include error messages if any
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">
                                    Mention steps to reproduce
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">
                                    Choose the right priority level
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Response Time Card --}}
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-timer fs-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="text-gray-900 fw-bold fs-5 mb-0">Response Time</h3>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="mb-0">
                            <div class="d-flex align-items-center mb-4">
                                <span class="badge badge-light-danger fw-semibold me-3 fs-7">High</span>
                                <span class="text-gray-700 fw-semibold fs-7">Within 2-4 hours</span>
                            </div>
                            <div class="d-flex align-items-center mb-4">
                                <span class="badge badge-light-warning fw-semibold me-3 fs-7">Medium</span>
                                <span class="text-gray-700 fw-semibold fs-7">Within 8-12 hours</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-light-success fw-semibold me-3 fs-7">Low</span>
                                <span class="text-gray-700 fw-semibold fs-7">Within 24-48 hours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Form validation
            document.getElementById('kt_ticket_form').addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="indicator-label">Please wait...</span>';
            });
        </script>
    @endpush

</x-default-layout>
