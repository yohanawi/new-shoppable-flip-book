<x-default-layout>

    @section('title')
        Catalog PDFs
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.index') }}
    @endsection

    <!--begin::Success Alert-->
    @if (session('success'))
        <div
            class="alert alert-dismissible bg-light-success border border-success border-dashed d-flex flex-column flex-sm-row p-5 mb-8">
            <i class="ki-outline ki-check-circle fs-2hx text-success me-4 mb-5 mb-sm-0"></i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1">Success!</h5>
                <span class="text-gray-700">{{ session('success') }}</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-success"></i>
            </button>
        </div>
    @endif
    <!--end::Success Alert-->

    <!--begin::Statistics-->
    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-row justify-content-between position-relative">
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fw-bold fs-7 mb-1">Total PDFs</span>
                        <span class="text-gray-900 fw-bold fs-2x">{{ $pdfs->total() }}</span>
                    </div>
                    <i
                        class="ki-outline ki-document text-primary fs-3x opacity-50 position-absolute end-0 bottom-0 mb-5 me-5"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-row justify-content-between position-relative">
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fw-bold fs-7 mb-1">Public</span>
                        <span
                            class="text-gray-900 fw-bold fs-2x">{{ $pdfs->where('visibility', 'public')->count() }}</span>
                    </div>
                    <i
                        class="ki-outline ki-eye text-success fs-3x opacity-50 position-absolute end-0 bottom-0 mb-5 me-5"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-row justify-content-between position-relative">
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fw-bold fs-7 mb-1">Private</span>
                        <span
                            class="text-gray-900 fw-bold fs-2x">{{ $pdfs->where('visibility', 'private')->count() }}</span>
                    </div>
                    <i
                        class="ki-outline ki-eye-slash text-warning fs-3x opacity-50 position-absolute end-0 bottom-0 mb-5 me-5"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex flex-row justify-content-between position-relative">
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fw-bold fs-7 mb-1">This Month</span>
                        <span
                            class="text-gray-900 fw-bold fs-2x">{{ $pdfs->where('created_at', '>=', now()->startOfMonth())->count() }}</span>
                    </div>
                    <i
                        class="ki-outline ki-calendar text-info fs-3x opacity-50 position-absolute end-0 bottom-0 mb-5 me-5"></i>
                </div>
            </div>
        </div>
    </div>
    <!--end::Statistics-->

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
        <form action="{{ route('catalog.pdfs.index') }}" method="GET" class="d-flex align-items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                class="form-control form-control-solid w-200px me-2" placeholder="Search PDFs...">
            <button type="submit" class="btn btn-light-primary">
                <i class="ki-outline ki-magnifier fs-2"></i>
            </button>
        </form>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('catalog.pdfs.create') }}" class="btn btn-primary fw-bold">
                <i class="ki-outline ki-plus fs-2"></i>
                Upload PDF
            </a>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::PDFs Grid-->
    @forelse ($pdfs as $pdf)
        @if ($loop->first)
            <div class="row g-6 g-xl-9">
        @endif

        <div class="col-md-6 col-xl-4">
            <!--begin::Card-->
            <div class="card h-100 shadow-sm hover-elevate-up">
                <!--begin::Card Header-->
                <div class="card-header border-0 pt-9 pb-0">
                    <div class="card-title m-0">
                        <div class="symbol symbol-50px w-50px">
                            <i class="ki-outline ki-file-sheet fs-2x text-primary"></i>
                        </div>
                        <div class="fs-4 fw-bold text-gray-900 mb-3">
                            <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="text-gray-900 text-hover-primary">
                                {{ Str::limit($pdf->title, 40) }}
                            </a>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        @if ($pdf->visibility === 'public')
                            <span class="badge badge-light-success fw-bold">
                                <i class="ki-outline ki-eye fs-7 me-1"></i>
                                Public
                            </span>
                        @else
                            <span class="badge badge-light-warning fw-bold">
                                <i class="ki-outline ki-eye-slash fs-7 me-1"></i>
                                Private
                            </span>
                        @endif
                    </div>
                </div>
                <!--end::Card Header-->

                <!--begin::Card Body-->
                <div class="card-body p-9">
                    <!--begin::Filename-->
                    @if ($pdf->original_filename)
                        <div class="d-flex align-items-center mb-5">
                            <i class="ki-outline ki-file fs-6 text-muted me-2"></i>
                            <span class="text-muted fs-7">{{ Str::limit($pdf->original_filename, 35) }}</span>
                        </div>
                    @endif
                    <!--end::Filename-->

                    <!--begin::Template Type-->
                    <div class="d-flex align-items-center mb-5">
                        <i class="ki-outline ki-setting-2 fs-6 text-muted me-2"></i>
                        <span class="badge badge-light fw-bold">
                            {{ \App\Models\CatalogPdf::templateTypeOptions()[$pdf->template_type] ?? $pdf->template_type }}
                        </span>
                    </div>
                    <!--end::Template Type-->

                    <!--begin::Date-->
                    <div class="d-flex align-items-center mb-7">
                        <i class="ki-outline ki-calendar fs-6 text-muted me-2"></i>
                        <span class="text-muted fs-7">{{ $pdf->created_at?->format('d M Y, h:i A') }}</span>
                    </div>
                    <!--end::Date-->

                    <!--begin::Actions-->
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                            class="btn btn-sm btn-primary fw-bold flex-grow-1">
                            <i class="ki-outline ki-eye fs-6 me-1"></i>
                            View
                        </a>

                        @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_FLIP_PHYSICS)
                            <a href="{{ route('catalog.pdfs.flip-physics.edit', $pdf) }}"
                                class="btn btn-sm btn-light-info fw-bold" title="Physics Editor">
                                <i class="ki-outline ki-rocket fs-6"></i>
                            </a>
                        @endif

                        @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_SLICER_SHOPPABLE)
                            <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}"
                                class="btn btn-sm btn-light-success fw-bold" title="Slicer Editor">
                                <i class="ki-outline ki-design-frame fs-6"></i>
                            </a>
                        @endif

                        @if ($pdf->template_type === \App\Models\CatalogPdf::TEMPLATE_PAGE_MANAGEMENT)
                            <a href="{{ route('catalog.pdfs.manage', $pdf) }}" class="btn btn-sm btn-light fw-bold"
                                title="Manage Pages">
                                <i class="ki-outline ki-notepad fs-6"></i>
                            </a>
                        @endif

                        <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-sm btn-light fw-bold"
                            title="Download PDF">
                            <i class="ki-outline ki-cloud-download fs-6"></i>
                        </a>
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Card Body-->
            </div>
            <!--end::Card-->
        </div>

        @if ($loop->last)
            </div>
            <!--end::PDFs Grid-->

            <!--begin::Pagination-->
            <div class="d-flex justify-content-center mt-10">
                {{ $pdfs->links() }}
            </div>
            <!--end::Pagination-->
        @endif

    @empty
        <!--begin::Empty State-->
        <div class="card shadow-sm">
            <div class="card-body p-20">
                <div class="text-center">
                    <!--begin::Illustration-->
                    <div class="mb-10">
                        <i class="ki-outline ki-file-deleted fs-5x text-muted opacity-50"></i>
                    </div>
                    <!--end::Illustration-->

                    <!--begin::Message-->
                    <div class="mb-7">
                        <h1 class="text-gray-900 mb-3">No PDFs Found</h1>
                        <div class="text-gray-500 fw-semibold fs-5">
                            You haven't uploaded any PDFs yet.<br>
                            Start by uploading your first catalog PDF.
                        </div>
                    </div>
                    <!--end::Message-->

                    <!--begin::Action-->
                    <a href="{{ route('catalog.pdfs.create') }}" class="btn btn-primary fw-bold">
                        <i class="ki-outline ki-plus fs-2"></i>
                        Upload Your First PDF
                    </a>
                    <!--end::Action-->
                </div>
            </div>
        </div>
        <!--end::Empty State-->
    @endforelse

</x-default-layout>
