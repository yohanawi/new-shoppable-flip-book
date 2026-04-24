<x-default-layout>

    @section('title')
        Create Support Ticket
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.create') }}
    @endsection

    <div id="kt_app_content_container">
        <div
            class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row p-5 mb-10">
            <i class="ki-duotone ki-notification-bing fs-2hx text-primary me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1">Need Help?</h5>
                <span class="text-gray-700">Describe the issue clearly and the support team will continue the
                    conversation inside the ticket thread.</span>
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
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Ticket Details</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Everything needed to create a real support
                                request.</span>
                        </h3>
                    </div>

                    <div class="card-body pt-0">
                        <form action="{{ route('tickets.store') }}" method="POST" id="kt_ticket_form"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="mb-8">
                                <label class="form-label required fs-6 fw-semibold text-gray-800">Subject</label>
                                <input type="text" name="subject"
                                    class="form-control form-control-solid @error('subject') is-invalid @enderror"
                                    value="{{ old('subject') }}" required placeholder="Brief description of your issue">
                                @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-7 mb-8">
                                <div class="col-md-6">
                                    <label class="form-label required fs-6 fw-semibold text-gray-800">Category</label>
                                    <select name="category_id"
                                        class="form-select form-select-solid @error('category_id') is-invalid @enderror"
                                        data-control="select2" data-hide-search="true" required>
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fs-6 fw-semibold text-gray-800">Priority (Optional)</label>
                                    <select name="priority"
                                        class="form-select form-select-solid @error('priority') is-invalid @enderror"
                                        data-hide-search="true" data-control="select2">
                                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High -
                                            urgent problem</option>
                                        <option value="medium"
                                            {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>
                                            Medium - standard support request
                                        </option>
                                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low -
                                            general question</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-8">
                                <label class="form-label fs-6 fw-semibold text-gray-800">Attachment (Optional)</label>
                                <input type="file" name="attachment"
                                    class="form-control form-control-solid @error('attachment') is-invalid @enderror"
                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.xlsx,.csv">
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">Max 10MB. Upload screenshots, invoices, or supporting
                                    documents.</div>
                            </div>

                            <div class="mb-10">
                                <label class="form-label required fs-6 fw-semibold text-gray-800">Message</label>
                                <textarea name="message" class="form-control form-control-solid @error('message') is-invalid @enderror" rows="10"
                                    required placeholder="Describe your issue in detail...">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    Include what happened, what you expected, and the steps to reproduce the issue.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3">
                                <a href="{{ route('tickets.index') }}" class="btn btn-light btn-active-light-primary">
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

            <div class="col-lg-4">
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
                                <div class="text-gray-700 fw-semibold fs-7">Use a subject that explains the problem
                                    clearly</div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">Attach screenshots or files when they help
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-4">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">New tickets start with status Open by
                                    default</div>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-gray-700 fw-semibold fs-7">You can reply later from the ticket
                                    conversation page</div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                    <h3 class="text-gray-900 fw-bold fs-5 mb-0">Response Guide</h3>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed mb-5"></div>

                        <div class="mb-0">
                            <div class="d-flex align-items-center mb-4">
                                <span class="badge badge-light-primary fw-semibold me-3 fs-7">Open</span>
                                <span class="text-gray-700 fw-semibold fs-7">Created automatically when you submit the
                                    ticket</span>
                            </div>
                            <div class="d-flex align-items-center mb-4">
                                <span class="badge badge-light-warning fw-semibold me-3 fs-7">In Progress</span>
                                <span class="text-gray-700 fw-semibold fs-7">Set by admin when the team starts working
                                    on it</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-light-success fw-semibold me-3 fs-7">Closed</span>
                                <span class="text-gray-700 fw-semibold fs-7">Submit a rating after the issue is
                                    resolved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('kt_ticket_form').addEventListener('submit', function() {
                const submitBtn = this.querySelector('[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="indicator-label">Please wait...</span>';
            });
        </script>
    @endpush

</x-default-layout>
