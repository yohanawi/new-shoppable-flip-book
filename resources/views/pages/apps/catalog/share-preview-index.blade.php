<x-default-layout>

    @section('title')
        Share Preview Studio
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.share-preview.index') }}
    @endsection

    @php($backgroundTypes = \App\Models\CatalogPdfSharePreviewSetting::backgroundTypeOptions())

    <div class="d-flex flex-column gap-8">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-start gap-3 border-0 shadow-sm mb-0">
                <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
                <div>
                    <div class="fw-bold mb-1">Share preview updated</div>
                    <div>{{ session('success') }}</div>
                </div>
            </div>
        @endif

        <div class="row g-6 g-xl-8">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-primary">
                    <div class="card-body p-7">
                        <div class="symbol symbol-44px mb-5">
                            <span class="symbol-label bg-white text-primary">
                                <i class="ki-outline ki-book-open fs-2"></i>
                            </span>
                        </div>
                        <div class="fs-6 text-muted fw-semibold mb-2">PDFs ready for sharing</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-success">
                    <div class="card-body p-7">
                        <div class="symbol symbol-44px mb-5">
                            <span class="symbol-label bg-white text-success">
                                <i class="ki-outline ki-picture fs-2"></i>
                            </span>
                        </div>
                        <div class="fs-6 text-muted fw-semibold mb-2">Configured preview layouts</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format($stats['configured']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-info">
                    <div class="card-body p-7">
                        <div class="symbol symbol-44px mb-5">
                            <span class="symbol-label bg-white text-info">
                                <i class="ki-outline ki-share fs-2"></i>
                            </span>
                        </div>
                        <div class="fs-6 text-muted fw-semibold mb-2">Public share links</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format($stats['public']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-warning">
                    <div class="card-body p-7">
                        <div class="symbol symbol-44px mb-5">
                            <span class="symbol-label bg-white text-warning">
                                <i class="ki-outline ki-video fs-2"></i>
                            </span>
                        </div>
                        <div class="fs-6 text-muted fw-semibold mb-2">Video-backed previews</div>
                        <div class="fs-2hx fw-bold text-gray-900">{{ number_format($stats['video_backgrounds']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pt-8 pb-0">
                <div class="card-title flex-column align-items-start">
                    <h3 class="fw-bold text-gray-900 mb-1">Share Preview Studio</h3>
                    <div class="text-muted fw-semibold fs-6">Use this workspace to control what readers see before they
                        start flipping through the PDF. Each row opens a detailed branding editor.</div>
                </div>
            </div>
            <div class="card-body pt-6">
                @if ($pdfs->total() === 0)
                    <div class="border border-dashed border-gray-300 rounded-4 px-10 py-15 text-center">
                        <i class="ki-outline ki-file-deleted fs-5x text-muted mb-5"></i>
                        <div class="fs-3 fw-bold text-gray-900 mb-2">No PDFs available yet</div>
                        <div class="text-muted fs-6 mb-6">Upload a catalog first, then return here to customize the
                            shared preview background and branding.</div>
                        <a href="{{ route('catalog.pdfs.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-3 me-2"></i>
                            Upload PDF
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-5 mb-0">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-250px">Catalog</th>
                                    <th class="min-w-140px">Workflow</th>
                                    <th class="min-w-150px">Share status</th>
                                    <th class="min-w-200px">Preview style</th>
                                    <th class="min-w-150px">Updated</th>
                                    <th class="text-end min-w-125px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pdfs as $pdf)
                                    @php($setting = $pdf->sharePreviewSetting)
                                    @php($appearanceType = $setting ? $backgroundTypes[$setting->effectiveBackgroundType()] ?? 'Solid color' : 'Default color')
                                    @php($isConfigured = $setting !== null)
                                    <tr class="cursor-pointer"
                                        data-row-link="{{ route('catalog.pdfs.share-preview.edit', $pdf) }}"
                                        tabindex="0">
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="fw-bold text-gray-900 fs-6">{{ $pdf->title }}</div>
                                                <div class="text-muted fs-7">
                                                    {{ $pdf->original_filename ?: 'Uploaded PDF' }}</div>
                                                @if (auth()->user()?->isAdmin())
                                                    <div class="text-muted fs-8">Owner:
                                                        {{ $pdf->user?->email ?? 'Unknown owner' }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary fs-8">
                                                {{ $templateTypes[$pdf->template_type] ?? $pdf->template_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-light-{{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'warning' }} text-capitalize mb-2">
                                                {{ $pdf->visibility }}
                                            </span>
                                            <div class="text-muted fs-8">
                                                {{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'Anyone with the link can see the preview' : 'Preview stays in the workspace' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="fw-semibold text-gray-800">{{ $appearanceType }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span
                                                        class="badge badge-light-{{ $isConfigured ? 'success' : 'secondary' }} fs-8">
                                                        {{ $isConfigured ? 'Configured' : 'Default' }}
                                                    </span>
                                                    @if ($setting?->hasLogo())
                                                        <span class="badge badge-light-warning fs-8">Logo</span>
                                                    @endif
                                                    @if (filled($setting?->logo_title))
                                                        <span class="badge badge-light-info fs-8">Title</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-gray-800 fw-semibold">
                                                {{ $setting?->updated_at?->format('d M Y') ?? 'Not configured' }}</div>
                                            <div class="text-muted fs-8">
                                                {{ $setting?->updated_at?->format('h:i A') ?? 'Uses project defaults' }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="{{ route('catalog.pdfs.share-preview.edit', $pdf) }}"
                                                    class="btn btn-sm btn-primary">
                                                    Customize
                                                </a>
                                                <a href="{{ route('catalog.pdfs.share', $pdf) }}"
                                                    class="btn btn-sm btn-light" target="_blank" rel="noopener">
                                                    Open
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-8">
                        {{ $pdfs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                document.querySelectorAll('[data-row-link]').forEach((row) => {
                    const openRow = () => {
                        window.location.href = row.dataset.rowLink;
                    };

                    row.addEventListener('click', (event) => {
                        if (event.target.closest('a, button, input, select, textarea, label')) {
                            return;
                        }

                        openRow();
                    });

                    row.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            openRow();
                        }
                    });
                });
            })();
        </script>
    @endpush

</x-default-layout>
