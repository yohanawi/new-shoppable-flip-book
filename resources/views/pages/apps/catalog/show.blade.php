<x-default-layout>

    @section('title')
        Manage PDF
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.show', $pdf) }}
    @endsection

    @php($workflowTypes = \App\Models\CatalogPdf::workflowTypeOptions())

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>
                <div class="fw-bold mb-1">PDF updated</div>
                <div>{{ session('success') }}</div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm overflow-hidden mb-8">
        <div class="card-body p-0">
            <div class="p-10 p-lg-15" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
                <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center">
                    <div class="mw-600px">
                        <span class="badge badge-light-primary mb-4">Workflow hub</span>
                        <h1 class="text-white fw-bold mb-4">{{ $pdf->title }}</h1>
                        <div class="d-flex flex-wrap gap-3">
                            <span
                                class="badge badge-light-{{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'warning' }} text-capitalize">
                                {{ $pdf->visibility }}
                            </span>

                            @if ($pdf->original_filename)
                                <span class="badge badge-light">{{ $pdf->original_filename }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light">
                            Back
                        </a>
                        <a href="{{ route('catalog.pdfs.share', $pdf) }}" class="btn btn-light-success" target="_blank">
                            Open Shared PDF
                        </a>
                        <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light-primary">
                            Download PDF
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                            data-bs-target="#deleteModal">
                            Delete PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-8">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-header border-0 pt-8">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Use all functions on this PDF</h3>
                        <div class="text-muted">Open any tool below. They all work on the same uploaded file.</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-6">
                        <div class="col-md-4">
                            <div class="border rounded-4 h-100 p-6 bg-light-primary d-flex flex-column">
                                <div class="symbol symbol-55px mb-5">
                                    <span class="symbol-label bg-white">
                                        <i class="ki-outline ki-notepad fs-2x text-primary"></i>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-3">Page Management</h4>
                                <div class="text-muted fs-7 mb-6 flex-grow-1">
                                    Reorder pages, rename them, hide pages, lock pages, and replace the PDF if needed.
                                </div>

                                <a href="{{ route('catalog.pdfs.manage', $pdf) }}"
                                    class="btn btn-primary w-100">Open</a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 h-100 p-6 bg-light-info d-flex flex-column">
                                <div class="symbol symbol-55px mb-5">
                                    <span class="symbol-label bg-white">
                                        <i class="ki-outline ki-rocket fs-2x text-info"></i>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-3">Flip Physics</h4>
                                <div class="text-muted fs-7 mb-6 flex-grow-1">
                                    Change page flip behavior, duration, elevation, display mode, and render quality.
                                </div>

                                <a href="{{ route('catalog.pdfs.flip-physics.edit', $pdf) }}"
                                    class="btn btn-info text-white w-100">Open</a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 h-100 p-6 bg-light-success d-flex flex-column">
                                <div class="symbol symbol-55px mb-5">
                                    <span class="symbol-label bg-white">
                                        <i class="ki-outline ki-shop fs-2x text-success"></i>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-gray-900 mb-3">Slicer</h4>
                                <div class="text-muted fs-7 mb-6 flex-grow-1">
                                    Build interactive hotspots, popup content, internal links, and shoppable areas.
                                </div>

                                <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}"
                                    class="btn btn-success w-100">Open</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-8">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">PDF preview</h3>
                        <div class="text-muted">The uploaded file stays visible here while you choose the next step.
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="rounded-4 overflow-hidden border" style="height: 70vh;">
                        <iframe src="{{ route('catalog.pdfs.file', $pdf) }}"
                            style="border: 0; width: 100%; height: 100%;" title="{{ $pdf->title }}"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border-0 shadow-sm mb-8">
                <div class="card-body p-8">
                    <h3 class="fw-bold text-gray-900 mb-5">PDF details</h3>
                    <div class="d-flex flex-column gap-5">
                        <div>
                            <div class="text-muted fs-7 mb-1">Description</div>
                            <div class="fw-semibold text-gray-800">{{ $pdf->description ?: 'No description added.' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 mb-1">Uploaded</div>
                            <div class="fw-semibold text-gray-800">{{ $pdf->created_at?->format('d M Y, h:i A') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 mb-1">Current focus</div>
                            <div class="fw-semibold text-gray-800">
                                {{ $workflowTypes[$pdf->template_type] ?? $pdf->template_type }}</div>
                        </div>
                        <div>
                            <div class="text-muted fs-7 mb-1">Share access</div>
                            <div class="fw-semibold text-gray-800">
                                @if ($pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC)
                                    Anyone with the share link can open this edited PDF.
                                @else
                                    This share link stays private and only authorized access is allowed.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-8">
                    <h3 class="fw-bold text-gray-900 mb-5">Quick actions</h3>
                    <div class="d-grid gap-3">
                        <div class="border rounded-3 p-4 bg-light">
                            <div class="fw-bold text-gray-900 mb-1">Set current focus</div>
                            <div class="text-muted fs-7 mb-3">This updates the primary badge and list summary only. All
                                features stay available.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <form action="{{ route('catalog.pdfs.workflow.select', $pdf) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="template_type"
                                        value="{{ \App\Models\CatalogPdf::TEMPLATE_PAGE_MANAGEMENT }}">
                                    <button type="submit" class="btn btn-sm btn-light-primary">Page
                                        Management</button>
                                </form>
                                <form action="{{ route('catalog.pdfs.workflow.select', $pdf) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="template_type"
                                        value="{{ \App\Models\CatalogPdf::TEMPLATE_FLIP_PHYSICS }}">
                                    <button type="submit" class="btn btn-sm btn-light-info">Flip Physics</button>
                                </form>
                                <form action="{{ route('catalog.pdfs.workflow.select', $pdf) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="template_type"
                                        value="{{ \App\Models\CatalogPdf::TEMPLATE_SLICER_SHOPPABLE }}">
                                    <button type="submit" class="btn btn-sm btn-light-success">Slicer</button>
                                </form>
                            </div>
                        </div>

                        <button type="button" class="btn btn-light-success"
                            onclick="copyToClipboard('{{ route('catalog.pdfs.share', $pdf) }}')">
                            Copy share link
                        </button>

                        <button type="button" class="btn btn-light"
                            onclick="copyToClipboard('{{ route('catalog.pdfs.show', $pdf) }}')">
                            Copy manage link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold text-danger mb-0">Delete PDF?</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-active-color-primary"
                        data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <div class="modal-body pt-5 pb-8 text-center">
                    <i class="ki-outline ki-trash fs-5x text-danger mb-5"></i>
                    <div class="text-gray-700 fs-6 mb-6">
                        Delete <strong>{{ $pdf->title }}</strong>? This action cannot be undone.
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('catalog.pdfs.delete', $pdf) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete PDF</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.copyToClipboard = function(text) {
                navigator.clipboard.writeText(text).then(() => {
                    if (window.toastr) {
                        toastr.success('Link copied to clipboard.');
                        return;
                    }

                    alert('Link copied to clipboard.');
                }).catch((error) => {
                    console.error(error);
                });
            };
        </script>
    @endpush

</x-default-layout>
