<x-default-layout>

    @section('title')
        Catalog PDFs
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.index') }}
    @endsection

    @php($hasFilters = filled($filters['search'] ?? null) || filled($filters['visibility'] ?? null) || filled($filters['template_type'] ?? null))
    @php($showingFrom = $pdfs->firstItem() ?? 0)
    @php($showingTo = $pdfs->lastItem() ?? 0)
    @php($canUploadPdf = $uploadAvailability['allowed'] ?? true)
    @php($uploadLimitMessage = $uploadAvailability['message'] ?? 'You have reached your PDF upload limit for the current plan.')
    @php($canViewAnalytics = (auth()->user()?->isAdmin() ?? false) || ((auth()->user()?->can('customer.analytics.view') ?? false) && app(\App\Services\BillingManager::class)->hasFeature(auth()->user(), 'analytics')))
    @php($titleUpdateBag = $errors->getBag('catalogTitleUpdate'))
    @php($propertiesModalPdfId = old('catalog_pdf_id'))
    @php($propertiesModalTitle = old('title', ''))

    <div class="d-flex flex-column gap-8">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-start gap-3 border-0 shadow-sm mb-0">
                <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
                <div>
                    <div class="fw-bold mb-1">Catalog updated</div>
                    <div>{{ session('success') }}</div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-8 p-lg-10">
                <form action="{{ route('catalog.pdfs.index') }}" method="GET" class="row g-4 align-items-end">
                    <div class="col-xl-4">
                        <label class="form-label fw-bold text-gray-900">Search PDFs</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}"
                            class="form-control form-control-solid" placeholder="Title, file name, or description">
                    </div>

                    <div class="col-md-4 col-xl-2">
                        <label class="form-label fw-bold text-gray-900">Visibility</label>
                        <select name="visibility" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All visibility</option>
                            @foreach ($visibilityOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($filters['visibility'] ?? null) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-xl-3">
                        <label class="form-label fw-bold text-gray-900">Workflow focus</label>
                        <select name="template_type" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All workflows</option>
                            @foreach ($templateTypeOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($filters['template_type'] ?? null) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-xl-3">
                        <label class="form-label fw-bold text-gray-900">Sort</label>
                        <select name="sort" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($filters['sort'] ?? 'latest') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-12">
                        <div class="d-flex flex-wrap gap-3 pt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-outline ki-magnifier fs-3 me-2"></i>
                                Apply filters
                            </button>
                            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                </form>

                @if ($hasFilters)
                    <div class="d-flex flex-wrap gap-3 mt-5">
                        @if (filled($filters['search']))
                            <span class="badge badge-light-primary fs-8 px-4 py-3">Search:
                                {{ $filters['search'] }}</span>
                        @endif

                        @if (filled($filters['visibility']))
                            <span class="badge badge-light-info fs-8 px-4 py-3">Visibility:
                                {{ $visibilityOptions[$filters['visibility']] ?? $filters['visibility'] }}</span>
                        @endif

                        @if (filled($filters['template_type']))
                            <span class="badge badge-light-warning fs-8 px-4 py-3">Workflow:
                                {{ $templateTypeOptions[$filters['template_type']] ?? $filters['template_type'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($pdfs->total() === 0)
            <div class="card shadow-sm">
                <div class="card-body py-20 px-10 text-center">
                    <i class="ki-outline ki-file-deleted fs-5x text-muted mb-5"></i>
                    <h2 class="fw-bold text-gray-900 mb-3">
                        {{ $hasFilters ? 'No PDFs match these filters' : 'No PDFs yet' }}
                    </h2>
                    <div class="text-muted fs-6 mb-6">
                        @if ($hasFilters)
                            Try clearing one of the filters or use a broader search term. The search checks title, file
                            name, and description.
                        @else
                            Upload your first PDF and it will appear here with quick actions, a small preview, and direct workflow access.
                        @endif
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        @if ($hasFilters)
                            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light">Clear filters</a>
                        @endif
                        <a href="{{ $canUploadPdf ? route('catalog.pdfs.create') : '#' }}"
                            class="btn btn-primary{{ $canUploadPdf ? '' : ' disabled' }}"
                            @if (!$canUploadPdf) data-upload-blocked="true" data-upload-limit-message="{{ e($uploadLimitMessage) }}" aria-disabled="true" @endif>
                            <i class="ki-outline ki-plus fs-3 me-2"></i>
                            Upload PDF
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <div class="fs-2 fw-bold text-gray-900 mb-1">Catalog workspace</div>
                    <div class="text-muted fw-semibold">Showing
                        {{ number_format($showingFrom) }}-{{ number_format($showingTo) }} of
                        {{ number_format($pdfs->total()) }} PDFs.</div>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <span class="badge badge-light-primary fs-8 px-4 py-3">
                        {{ number_format($pdfs->count()) }} on this page
                    </span>
                    <span class="badge badge-light-dark fs-8 px-4 py-3">
                        Sort: {{ $sortOptions[$filters['sort'] ?? 'latest'] ?? 'Newest first' }}
                    </span>

                    <a href="{{ $canUploadPdf ? route('catalog.pdfs.create') : '#' }}"
                        class="btn btn-warning btn-xs fw-bold{{ $canUploadPdf ? '' : ' disabled' }}"
                        @if (!$canUploadPdf) data-upload-blocked="true" data-upload-limit-message="{{ e($uploadLimitMessage) }}" aria-disabled="true" @endif>
                        <i class="ki-outline ki-plus fs-3 me-2"></i>
                        Upload New PDF
                    </a>

                    @if ($hasFilters)
                        <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light btn-lg">
                            <i class="ki-outline ki-cross-circle fs-3 me-2"></i>
                            Clear filters
                        </a>
                    @endif

                </div>
            </div>

            <div class="row g-6 g-xl-8">
                @foreach ($pdfs as $pdf)
                    @php($shareUrl = route('catalog.pdfs.share', $pdf))
                    @php($previewStudioUrl = route('catalog.pdfs.share-preview.edit', $pdf))

                    <div class="col-md-6 col-xl-4 col-xxl-3">

                        <div class="card card-flush h-100 shadow-sm">
                            <div class="card-body p-5 p-lg-6 d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-light-info fs-8">
                                            {{ $templateTypeOptions[$pdf->template_type] ?? $pdf->template_type }}
                                        </span>
                                        <span class="badge badge-light-{{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'warning' }} text-capitalize fs-8">
                                            {{ $pdf->visibility }}
                                        </span>
                                    </div>

                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-icon btn-sm btn-light"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                            <i class="ki-outline ki-dots-horizontal fs-2 text-gray-700"></i>
                                        </button>

                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-225px"
                                            data-kt-menu="true">
                                            <div class="menu-item px-3">
                                                <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                                                    class="menu-link px-3">
                                                    <span class="menu-icon">
                                                        <i class="ki-outline ki-notepad-edit fs-3"></i>
                                                    </span>
                                                    <span class="menu-title">Edit</span>
                                                </a>
                                            </div>

                                            <div class="menu-item px-3">
                                                <button type="button"
                                                    class="menu-link px-3 border-0 bg-transparent w-100 text-start"
                                                    data-bs-toggle="modal" data-bs-target="#catalogPdfPropertiesModal"
                                                    data-properties-action="{{ route('catalog.pdfs.update', $pdf) }}"
                                                    data-properties-pdf-id="{{ $pdf->id }}"
                                                    data-properties-title="{{ $pdf->title }}"
                                                    data-properties-filename="{{ $pdf->original_filename ?: 'Uploaded PDF' }}">
                                                    <span class="menu-icon">
                                                        <i class="ki-outline ki-gear fs-3"></i>
                                                    </span>
                                                    <span class="menu-title">Properties</span>
                                                </button>
                                            </div>

                                            <div class="menu-item px-3">
                                                @if ($canViewAnalytics)
                                                    <a href="{{ route('analytics.index') }}" class="menu-link px-3">
                                                        <span class="menu-icon">
                                                            <i class="ki-outline ki-chart-line-up fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">View Analytics</span>
                                                    </a>
                                                @else
                                                    <button type="button"
                                                        class="menu-link px-3 border-0 bg-transparent w-100 text-start"
                                                        data-disabled-action="Analytics is not available for this account.">
                                                        <span class="menu-icon">
                                                            <i class="ki-outline ki-chart-line-up fs-3"></i>
                                                        </span>
                                                        <span class="menu-title">View Analytics</span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="menu-item px-3">
                                                @if ($pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC)
                                                    <form action="{{ route('catalog.pdfs.unpublish', $pdf) }}"
                                                        method="POST" data-swal-confirm
                                                        data-swal-title="Unpublish PDF?"
                                                        data-swal-text="This will make {{ e($pdf->title) }} private and disable public share access."
                                                        data-swal-confirm-text="Yes, unpublish">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="menu-link px-3 border-0 bg-transparent w-100 text-start">
                                                            <span class="menu-icon">
                                                                <i class="ki-outline ki-eye-slash fs-3"></i>
                                                            </span>
                                                            <span class="menu-title">Unpublish</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('catalog.pdfs.publish', $pdf) }}"
                                                        method="POST" data-swal-confirm
                                                        data-swal-title="Publish PDF?"
                                                        data-swal-text="This will make {{ e($pdf->title) }} public and enable share access."
                                                        data-swal-confirm-text="Yes, publish">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="menu-link px-3 border-0 bg-transparent w-100 text-start">
                                                            <span class="menu-icon">
                                                                <i class="ki-outline ki-eye fs-3"></i>
                                                            </span>
                                                            <span class="menu-title">Publish</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            <div class="menu-item px-3">
                                                <a href="{{ $previewStudioUrl }}" class="menu-link px-3">
                                                    <span class="menu-icon">
                                                        <i class="ki-outline ki-slider-horizontal fs-3"></i>
                                                    </span>
                                                    <span class="menu-title">Preview Studio</span>
                                                </a>
                                            </div>

                                            <div class="separator my-2"></div>

                                            <div class="menu-item px-3">
                                                <button type="button"
                                                    class="menu-link px-3 border-0 bg-transparent w-100 text-start"
                                                    data-copy-link="{{ $shareUrl }}">
                                                    <span class="menu-icon">
                                                        <i class="ki-outline ki-copy fs-3"></i>
                                                    </span>
                                                    <span class="menu-title">Copy Link</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-4 bg-light overflow-hidden mb-4">
                                    <div class="ratio ratio-16x9 bg-light-primary">
                                        <iframe
                                            src="{{ route('catalog.pdfs.file', $pdf) }}#toolbar=0&navpanes=0&scrollbar=0"
                                            title="{{ $pdf->title }} preview" class="w-100 h-100 border-0"
                                            loading="lazy">
                                        </iframe>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <div class="border border-dashed border-gray-300 rounded-3 px-3 py-2">
                                        <div class="text-muted text-uppercase fw-bold fs-8 mb-1">Uploaded</div>
                                        <div class="text-gray-900 fw-bold fs-7">
                                            {{ $pdf->created_at?->format('d M Y') }}</div>
                                    </div>

                                    <div class="border border-dashed border-gray-300 rounded-3 px-3 py-2">
                                        <div class="text-muted text-uppercase fw-bold fs-8 mb-1">File size</div>
                                        <div class="text-gray-900 fw-bold fs-7">
                                            {{ number_format(max(($pdf->size ?? 0) / 1048576, 0.01), 2) }} MB</div>
                                    </div>
                                </div>
                                <div class="separator separator-dashed mb-4"></div>
                                <div class="d-flex align-items-center justify-content-between gap-3 mt-auto">
                                    <div class="min-w-0">
                                        <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                                            class="text-gray-900 text-hover-primary fw-bold fs-6 d-block text-truncate mb-1">
                                            {{ $pdf->title }}
                                        </a>
                                        <div class="text-muted fs-7 text-truncate">
                                            {{ $pdf->original_filename ?: 'Uploaded PDF' }}</div>
                                    </div>

                                    <div class="text-end">
                                        <span
                                            class="badge badge-light-{{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'warning' }} text-capitalize mb-2">{{ $pdf->visibility }}</span>
                                        <div class="text-muted fs-8">
                                            {{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'Shared link ready' : 'Private workspace' }}
                                        </div>
                                    </div>
                                </div>

                                @if (auth()->user()?->isAdmin())
                                    <div class="text-muted fs-8 mt-3 pt-3 border-top border-gray-100 text-truncate">
                                        Owner: {{ $pdf->user?->email ?? 'Unknown owner' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-10">
                {{ $pdfs->links() }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="catalogPdfPropertiesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-sm border-0">
                <form method="POST" id="catalogPdfPropertiesForm">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header border-0 pb-0 px-8 pt-8">
                        <div>
                            <h3 class="fw-bold text-gray-900 mb-1">Catalog PDF properties</h3>
                            <div class="text-muted fs-7">Update the title shown across the catalog workspace and share
                                flows.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-active-light-primary"
                            data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </div>

                    <div class="modal-body px-8 py-7">
                        <input type="hidden" name="catalog_pdf_id" id="catalogPdfPropertiesId"
                            value="{{ $propertiesModalPdfId }}">

                        <div class="rounded-3 bg-light-primary border border-primary border-dashed px-4 py-3 mb-6">
                            <div class="fw-bold text-gray-900 fs-7 mb-1" id="catalogPdfPropertiesFileName">Select a
                                PDF</div>
                            <div class="text-muted fs-8">Only the title changes here. The PDF file and workflow stay
                                the same.</div>
                        </div>

                        <div class="mb-2">
                            <label for="catalogPdfPropertiesTitle"
                                class="form-label fw-bold text-gray-900 required">PDF title</label>
                            <input type="text" name="title" id="catalogPdfPropertiesTitle"
                                value="{{ $propertiesModalTitle }}"
                                class="form-control form-control-lg form-control-solid{{ $titleUpdateBag->has('title') ? ' is-invalid' : '' }}"
                                placeholder="Example: Spring Collection Catalog" required maxlength="255">
                            @if ($titleUpdateBag->has('title'))
                                <div class="invalid-feedback">{{ $titleUpdateBag->first('title') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-8 pb-8 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-check fs-3 me-2"></i>
                            Save title
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const defaultMessage = @json($uploadLimitMessage);
                const billingError = @json($errors->first('billing'));
                const propertiesModalId = 'catalogPdfPropertiesModal';
                const propertiesModalElement = document.getElementById(propertiesModalId);
                const propertiesForm = document.getElementById('catalogPdfPropertiesForm');
                const propertiesIdInput = document.getElementById('catalogPdfPropertiesId');
                const propertiesTitleInput = document.getElementById('catalogPdfPropertiesTitle');
                const propertiesFileName = document.getElementById('catalogPdfPropertiesFileName');
                const hasPropertiesErrors = @json($titleUpdateBag->any());
                const previousPropertiesPdfId = @json($propertiesModalPdfId);

                function showAlert(options) {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire(options);
                        return;
                    }

                    window.alert(options.text || defaultMessage);
                }

                document.querySelectorAll('[data-upload-blocked="true"]').forEach((link) => {
                    link.addEventListener('click', (event) => {
                        event.preventDefault();
                        showAlert({
                            icon: 'warning',
                            title: 'Upload limit reached',
                            text: link.dataset.uploadLimitMessage || defaultMessage,
                            confirmButtonText: 'OK'
                        });
                    });
                });

                document.querySelectorAll('[data-copy-link]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const link = button.dataset.copyLink;

                        try {
                            if (navigator.clipboard && typeof navigator.clipboard.writeText ===
                                'function') {
                                await navigator.clipboard.writeText(link);
                            } else {
                                const input = document.createElement('input');
                                input.value = link;
                                document.body.appendChild(input);
                                input.select();
                                document.execCommand('copy');
                                input.remove();
                            }

                            showAlert({
                                icon: 'success',
                                title: 'Link copied',
                                text: 'The share link has been copied to your clipboard.',
                                confirmButtonText: 'OK'
                            });
                        } catch (error) {
                            showAlert({
                                icon: 'error',
                                title: 'Copy failed',
                                text: 'Unable to copy the share link automatically.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                });

                document.querySelectorAll('[data-disabled-action]').forEach((button) => {
                    button.addEventListener('click', () => {
                        showAlert({
                            icon: 'info',
                            title: 'Action unavailable',
                            text: button.dataset.disabledAction,
                            confirmButtonText: 'OK'
                        });
                    });
                });

                document.querySelectorAll('[data-properties-action]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (!propertiesForm || !propertiesTitleInput || !propertiesFileName || !
                            propertiesIdInput) {
                            return;
                        }

                        propertiesForm.action = button.dataset.propertiesAction;
                        propertiesIdInput.value = button.dataset.propertiesPdfId || '';
                        propertiesTitleInput.value = button.dataset.propertiesTitle || '';
                        propertiesFileName.textContent = button.dataset.propertiesFilename ||
                            'Uploaded PDF';
                    });
                });

                if (hasPropertiesErrors && propertiesModalElement && propertiesForm) {
                    const fallbackTrigger = document.querySelector(
                        `[data-properties-pdf-id="${previousPropertiesPdfId}"]`
                    );

                    if (fallbackTrigger) {
                        propertiesForm.action = fallbackTrigger.dataset.propertiesAction;
                        propertiesFileName.textContent = fallbackTrigger.dataset.propertiesFilename || 'Uploaded PDF';
                    }

                    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        window.bootstrap.Modal.getOrCreateInstance(propertiesModalElement).show();
                    }
                }

                if (billingError) {
                    showAlert({
                        icon: 'warning',
                        title: 'Upload limit reached',
                        text: billingError,
                        confirmButtonText: 'OK'
                    });
                }
            })();
        </script>
    @endpush

</x-default-layout>
