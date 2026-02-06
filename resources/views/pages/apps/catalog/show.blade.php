<x-default-layout>
    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
        <!--begin::Page Title-->
        <div class="page-title d-flex flex-column justify-content-center gap-2 me-3">
            <h1 class="page-heading d-flex align-items-center text-gray-900 fw-bold fs-3 m-0">
                {{ $pdf->title }}
                @if ($pdf->visibility === 'public')
                    <span class="badge badge-light-success fs-7 fw-bold ms-3">
                        <i class="ki-outline ki-eye fs-7 me-1"></i>
                        Public
                    </span>
                @else
                    <span class="badge badge-light-warning fs-7 fw-bold ms-3">
                        <i class="ki-outline ki-eye-slash fs-7 me-1"></i>
                        Private
                    </span>
                @endif
            </h1>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge badge-light fw-bold">
                    <i class="ki-outline ki-setting-2 fs-7 me-1"></i>
                    {{ $templateTypes[$pdf->template_type] ?? $pdf->template_type }}
                </span>
                @if ($pdf->original_filename)
                    <span class="text-muted fs-7 fw-semibold">
                        <i class="ki-outline ki-file fs-7 me-1"></i>
                        {{ $pdf->original_filename }}
                    </span>
                @endif
                <span class="text-muted fs-7 fw-semibold">
                    <i class="ki-outline ki-calendar fs-7 me-1"></i>
                    {{ $pdf->created_at?->format('d M Y, h:i A') }}
                </span>
            </div>
        </div>
        <!--end::Page Title-->

        <!--begin::Actions-->
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_PAGE_MANAGEMENT)
                <a href="{{ route('catalog.pdfs.manage', $pdf) }}" class="btn btn-sm btn-light-primary fw-bold">
                    <i class="ki-outline ki-notepad fs-4 me-1"></i>
                    Manage Pages
                </a>
                <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-sm btn-primary fw-bold">
                    <i class="ki-outline ki-eye fs-4 me-1"></i>
                    Preview Flipbook
                </a>
            @endif

            @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_FLIP_PHYSICS)
                <a href="{{ route('catalog.pdfs.flip-physics.edit', $pdf) }}" class="btn btn-sm btn-light-info fw-bold">
                    <i class="ki-outline ki-rocket fs-4 me-1"></i>
                    Flip Physics
                </a>
            @endif

            @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_SLICER_SHOPPABLE)
                <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}" class="btn btn-sm btn-light-success fw-bold">
                    <i class="ki-outline ki-design-frame fs-4 me-1"></i>
                    Slicer Editor
                </a>
                <a href="{{ route('catalog.pdfs.slicer.preview', $pdf) }}" class="btn btn-sm btn-primary fw-bold">
                    <i class="ki-outline ki-shop fs-4 me-1"></i>
                    Shoppable Preview
                </a>
            @endif

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-light fw-bold dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ki-outline ki-setting-2 fs-4 me-1"></i>
                    More Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('catalog.pdfs.download', $pdf) }}">
                            <i class="ki-outline ki-cloud-download fs-5 me-2"></i>
                            Download PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="window.print(); return false;">
                            <i class="ki-outline ki-printer fs-5 me-2"></i>
                            Print
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#deleteModal">
                            <i class="ki-outline ki-trash fs-5 me-2"></i>
                            Delete PDF
                        </a>
                    </li>
                </ul>
            </div>

            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-sm btn-light-primary fw-bold">
                <i class="ki-outline ki-left fs-4 me-1"></i>
                Back
            </a>
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Description Alert-->
    @if ($pdf->description)
        <div
            class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex align-items-center p-5 mb-8">
            <i class="ki-outline ki-information-5 fs-2hx text-primary me-4"></i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 fw-bold">Description</h5>
                <span class="text-gray-700">{{ $pdf->description }}</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-primary"></i>
            </button>
        </div>
    @endif
    <!--end::Description Alert-->

    <div class="row g-7">
        <!--begin::PDF Viewer-->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <!--begin::Card Header-->
                <div class="card-header border-0 bg-light-primary">
                    <div class="card-title m-0">
                        <h3 class="fw-bold text-gray-900 m-0">
                            <i class="ki-outline ki-file-sheet fs-2 text-primary me-2"></i>
                            PDF Viewer
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-icon btn-light-primary" id="fullscreen"
                                title="Fullscreen">
                                <i class="ki-outline ki-screen fs-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Card Header-->

                <!--begin::Card Body-->
                <div class="card-body p-0 position-relative" style="height: 75vh;" id="pdfViewerContainer">
                    <iframe src="{{ route('catalog.pdfs.file', $pdf) }}" style="border:0; width:100%; height:100%;"
                        title="{{ $pdf->title }}"></iframe>


                    <!--end::Loading Overlay-->
                </div>
                <!--end::Card Body-->
            </div>
        </div>
        <!--end::PDF Viewer-->

        <!--begin::Info Sidebar-->
        <div class="col-lg-3">
            <!--begin::Details Card-->
            <div class="card shadow-sm mb-7">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">PDF Details</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <!--begin::Details List-->
                    <div class="mb-7">
                        <div class="d-flex align-items-center mb-5">
                            <div class="symbol symbol-40px me-4">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-outline ki-tag fs-2 text-primary"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold d-block fs-8">Title</span>
                                <span class="text-gray-900 fw-bold fs-7">{{ $pdf->title }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <div class="symbol symbol-40px me-4">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-outline ki-setting-2 fs-2 text-success"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold d-block fs-8">Template</span>
                                <span
                                    class="text-gray-900 fw-bold fs-7">{{ $templateTypes[$pdf->template_type] ?? $pdf->template_type }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <div class="symbol symbol-40px me-4">
                                <span
                                    class="symbol-label bg-light-{{ $pdf->visibility === 'public' ? 'success' : 'warning' }}">
                                    <i
                                        class="ki-outline ki-{{ $pdf->visibility === 'public' ? 'eye' : 'eye-slash' }} fs-2 text-{{ $pdf->visibility === 'public' ? 'success' : 'warning' }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold d-block fs-8">Visibility</span>
                                <span class="text-gray-900 fw-bold fs-7 text-capitalize">{{ $pdf->visibility }}</span>
                            </div>
                        </div>

                        @if ($pdf->original_filename)
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-40px me-4">
                                    <span class="symbol-label bg-light-info">
                                        <i class="ki-outline ki-file fs-2 text-info"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="text-muted fw-semibold d-block fs-8">Filename</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-7">{{ Str::limit($pdf->original_filename, 20) }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-4">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-outline ki-calendar fs-2 text-warning"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted fw-semibold d-block fs-8">Uploaded</span>
                                <span
                                    class="text-gray-900 fw-bold fs-7">{{ $pdf->created_at?->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Details List-->
                </div>
            </div>
            <!--end::Details Card-->

            <!--begin::Quick Actions Card-->
            <div class="card shadow-sm mb-7 bg-light-primary border-0">
                <div class="card-body p-7">
                    <h3 class="text-gray-900 fw-bold mb-5">Quick Actions</h3>
                    <div class="d-grid gap-3">
                        @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_SLICER_SHOPPABLE)
                            <a href="{{ route('catalog.pdfs.slicer.live', $pdf) }}"
                                class="btn btn-success btn-sm fw-bold" target="_blank">
                                <i class="ki-outline ki-rocket fs-4 me-2"></i>
                                Go Live
                            </a>
                        @endif

                        <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-primary btn-sm fw-bold">
                            <i class="ki-outline ki-cloud-download fs-4 me-2"></i>
                            Download
                        </a>

                        <button type="button" class="btn btn-light btn-sm fw-bold"
                            onclick="copyToClipboard('{{ route('catalog.pdfs.show', $pdf) }}')">
                            <i class="ki-outline ki-copy fs-4 me-2"></i>
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>
            <!--end::Quick Actions Card-->

            <!--begin::Help Card-->
            <div class="card shadow-sm border-0">
                <div class="card-body p-7">
                    <div class="text-center">
                        <i class="ki-outline ki-question-2 fs-5x text-muted mb-5"></i>
                        <h3 class="text-gray-900 fw-bold mb-3">Need Help?</h3>
                        <p class="text-gray-600 fw-semibold fs-7 mb-5">
                            Learn how to make the most of your PDF catalog features
                        </p>
                        <a href="#" class="btn btn-sm btn-light-primary fw-bold">
                            <i class="ki-outline ki-book fs-4 me-1"></i>
                            View Documentation
                        </a>
                    </div>
                </div>
            </div>
            <!--end::Help Card-->
        </div>
        <!--end::Info Sidebar-->
    </div>

    <!--begin::Delete Modal-->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold text-danger">Delete PDF?</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body pt-5 pb-8">
                    <div class="text-center mb-5">
                        <i class="ki-outline ki-trash fs-5x text-danger mb-5"></i>
                        <p class="text-gray-700 fs-6">
                            Are you sure you want to delete <strong>{{ $pdf->title }}</strong>?
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="btn btn-light fw-bold px-8" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <form action="{{ route('catalog.pdfs.delete', $pdf) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger fw-bold px-8">
                                <i class="ki-outline ki-trash fs-4 me-2"></i>
                                Yes, Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Delete Modal-->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Hide loading overlay when iframe loads
                const iframe = document.getElementById('pdfIframe');
                const overlay = document.getElementById('loadingOverlay');

                iframe.addEventListener('load', () => {
                    overlay.style.display = 'none';
                });

                // Fullscreen functionality
                const fullscreenBtn = document.getElementById('fullscreen');
                const container = document.getElementById('pdfViewerContainer');

                fullscreenBtn?.addEventListener('click', function() {
                    if (!document.fullscreenElement) {
                        container.requestFullscreen().catch(err => {
                            console.error('Fullscreen error:', err);
                        });
                    } else {
                        document.exitFullscreen();
                    }
                });

                // Copy to clipboard function
                window.copyToClipboard = function(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        // Show success message (you can use toastr or any notification library)
                        if (window.toastr) {
                            toastr.success('Link copied to clipboard!');
                        } else {
                            alert('Link copied to clipboard!');
                        }
                    }).catch(err => {
                        console.error('Copy failed:', err);
                    });
                };
            });
        </script>
    @endpush

</x-default-layout>
