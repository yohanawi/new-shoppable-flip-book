<x-default-layout>

    @section('title')
        Create New PDF Catalog
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.create') }}
    @endsection

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
              <!--begin::Actions-->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-sm btn-light-primary fw-bold">
                <i class="ki-outline ki-left fs-4 me-1"></i>
                Back to List
            </a>
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Toolbar-->

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

    <form action="{{ route('catalog.pdfs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-7">
            <!--begin::Left Column - Main Details-->
            <div class="col-lg-8">
                <!--begin::Basic Information Card-->
                <div class="card shadow-sm mb-7">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Basic Information</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Enter the PDF title and description</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <!--begin::Title-->
                        <div class="mb-10">
                            <label class="form-label required fs-6 fw-semibold text-gray-800 mb-3">
                                <i class="ki-outline ki-tag fs-5 text-gray-500 me-1"></i>
                                PDF Title
                            </label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                class="form-control form-control-lg form-control-solid @error('title') is-invalid @enderror"
                                placeholder="e.g., Spring Summer 2024 Catalog" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Give your PDF a descriptive and memorable title</div>
                        </div>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="mb-0">
                            <label class="form-label fs-6 fw-semibold text-gray-800 mb-3">
                                <i class="ki-outline ki-note-2 fs-5 text-gray-500 me-1"></i>
                                Description
                                <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea name="description" rows="5"
                                class="form-control form-control-lg form-control-solid @error('description') is-invalid @enderror"
                                placeholder="Brief description about this catalog...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Add details to help identify this PDF later</div>
                        </div>
                        <!--end::Description-->
                    </div>
                </div>
                <!--end::Basic Information Card-->

                <!--begin::File Upload Card-->
                <div class="card shadow-sm">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">PDF File</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Select your PDF file to upload</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <!--begin::File Input-->
                        <div class="mb-0">
                            <label class="form-label required fs-6 fw-semibold text-gray-800 mb-3">
                                <i class="ki-outline ki-file-up fs-5 text-gray-500 me-1"></i>
                                Choose PDF File
                            </label>

                            <!--begin::Upload Area-->
                            <div
                                class="upload-area border-2 border-dashed border-gray-300 rounded-3 p-10 text-center position-relative @error('pdf') border-danger @enderror">
                                <input type="file" name="pdf" accept="application/pdf" id="pdfFileInput"
                                    class="form-control form-control-lg form-control-solid position-absolute opacity-0 top-0 start-0 w-100 h-100"
                                    style="cursor: pointer; z-index: 1;" required>

                                <div class="upload-placeholder" id="uploadPlaceholder">
                                    <i class="ki-outline ki-file-added fs-5x text-primary mb-5"></i>
                                    <h3 class="fs-5 fw-bold text-gray-900 mb-2">Drop PDF here or click to upload</h3>
                                    <span class="fs-7 fw-semibold text-gray-500">PDF only • Maximum file size:
                                        20MB</span>
                                </div>

                                <div class="upload-preview d-none" id="uploadPreview">
                                    <i class="ki-outline ki-file-sheet fs-5x text-success mb-3"></i>
                                    <h3 class="fs-6 fw-bold text-gray-900 mb-1" id="fileName"></h3>
                                    <span class="fs-7 text-muted" id="fileSize"></span>
                                    <button type="button" class="btn btn-sm btn-light-danger mt-4" id="removeFile">
                                        <i class="ki-outline ki-trash fs-6 me-1"></i>
                                        Remove File
                                    </button>
                                </div>
                            </div>
                            <!--end::Upload Area-->

                            @error('pdf')
                                <div class="text-danger mt-2 fs-7 fw-semibold">{{ $message }}</div>
                            @enderror

                            <!--begin::File Requirements-->
                            <div
                                class="alert alert-dismissible bg-light-info border border-info border-dashed d-flex align-items-center p-5 mt-5">
                                <i class="ki-outline ki-information-5 fs-2hx text-info me-4"></i>
                                <div class="d-flex flex-column pe-0 pe-sm-10">
                                    <h5 class="mb-1">File Requirements</h5>
                                    <span class="text-gray-700">
                                        • File format: PDF only<br>
                                        • Maximum size: 20MB<br>
                                        • Recommended: High-resolution for best quality
                                    </span>
                                </div>
                            </div>
                            <!--end::File Requirements-->
                        </div>
                        <!--end::File Input-->
                    </div>
                </div>
                <!--end::File Upload Card-->
            </div>
            <!--end::Left Column-->

            <!--begin::Right Column - Configuration-->
            <div class="col-lg-4">
                <!--begin::Template Type Card-->
                <div class="card shadow-sm mb-7">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Template Type</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Choose how to display your PDF</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <!--begin::Template Options-->
                        <div class="row g-5">
                            <!--begin::Page Management-->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="template_type" value="page_management"
                                    id="template_page"
                                    {{ old('template_type') === 'page_management' ? 'checked' : '' }}>
                                <label
                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-7 d-flex align-items-start w-100 text-start"
                                    for="template_page">
                                    <span class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-notepad fs-2x text-primary"></i>
                                        </span>
                                    </span>
                                    <span class="d-block">
                                        <span class="text-gray-900 fw-bold d-block fs-6 mb-2">Page Management</span>
                                        <span class="text-gray-500 fw-semibold fs-7">Reorder, rename, and organize
                                            pages efficiently</span>
                                    </span>
                                </label>
                            </div>
                            <!--end::Page Management-->

                            <!--begin::Flip Physics-->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="template_type" value="flip_physics"
                                    id="template_physics"
                                    {{ old('template_type') === 'flip_physics' ? 'checked' : '' }}>
                                <label
                                    class="btn btn-outline btn-outline-dashed btn-active-light-success p-7 d-flex align-items-start w-100 text-start"
                                    for="template_physics">
                                    <span class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-outline ki-rocket fs-2x text-success"></i>
                                        </span>
                                    </span>
                                    <span class="d-block">
                                        <span class="text-gray-900 fw-bold d-block fs-6 mb-2">Flip Physics</span>
                                        <span class="text-gray-500 fw-semibold fs-7">Realistic page flip animations and
                                            effects</span>
                                    </span>
                                </label>
                            </div>
                            <!--end::Flip Physics-->

                            <!--begin::Slicer (Shoppable)-->
                            <div class="col-12">
                                <input type="radio" class="btn-check" name="template_type"
                                    value="slicer_shoppable" id="template_slicer"
                                    {{ old('template_type') === 'slicer_shoppable' ? 'checked' : '' }}>
                                <label
                                    class="btn btn-outline btn-outline-dashed btn-active-light-warning p-7 d-flex align-items-start w-100 text-start position-relative"
                                    for="template_slicer">
                                    <span class="badge badge-warning position-absolute top-0 end-0 m-3 fw-bold">
                                        <i class="ki-outline ki-star fs-7 me-1"></i>
                                        Popular
                                    </span>
                                    <span class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-warning">
                                            <i class="ki-outline ki-shop fs-2x text-warning"></i>
                                        </span>
                                    </span>
                                    <span class="d-block">
                                        <span class="text-gray-900 fw-bold d-block fs-6 mb-2">Slicer (Shoppable)</span>
                                        <span class="text-gray-500 fw-semibold fs-7">Add interactive hotspots and
                                            product links</span>
                                    </span>
                                </label>
                            </div>
                            <!--end::Slicer (Shoppable)-->
                        </div>
                        <!--end::Template Options-->

                        @error('template_type')
                            <div class="text-danger mt-3 fs-7 fw-semibold">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <!--end::Template Type Card-->

                <!--begin::Visibility Card-->
                <div class="card shadow-sm mb-7">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Visibility</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Control who can access this PDF</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <!--begin::Visibility Select-->
                        <div class="mb-3">
                            <label class="form-label required fs-6 fw-semibold text-gray-800 mb-3">
                                <i class="ki-outline ki-eye fs-5 text-gray-500 me-1"></i>
                                Access Level
                            </label>
                            <select name="visibility"
                                class="form-select form-select-lg form-select-solid @error('visibility') is-invalid @enderror"
                                data-control="select2" data-hide-search="true" required>
                                @foreach ($visibilityOptions as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('visibility', 'private') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('visibility')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <!--end::Visibility Select-->

                        <!--begin::Info Notice-->
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-5">
                            <i class="ki-outline ki-shield-tick fs-2tx text-info me-4"></i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <h4 class="text-gray-900 fw-bold mb-2">Storage Info</h4>
                                    <div class="fs-7 text-gray-700">
                                        <strong>Public:</strong> Stored on public disk for faster access<br>
                                        <strong>Private:</strong> Stored locally, served securely via app
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end::Info Notice-->
                    </div>
                </div>
                <!--end::Visibility Card-->

                <!--begin::Submit Card-->
                <div class="card shadow-sm bg-light-primary border-0">
                    <div class="card-body p-7">
                        <div class="text-center mb-5">
                            <i class="ki-outline ki-cloud-add fs-5x text-primary mb-3"></i>
                            <h3 class="text-gray-900 fw-bold mb-2">Ready to Upload?</h3>
                            <p class="text-gray-600 fw-semibold fs-7 mb-0">
                                Make sure all fields are filled correctly before uploading
                            </p>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-lg btn-primary fw-bold">
                                <i class="ki-outline ki-cloud-upload fs-2 me-2"></i>
                                Upload PDF Now
                            </button>
                            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-lg btn-light fw-bold">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
                <!--end::Submit Card-->
            </div>
            <!--end::Right Column-->
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fileInput = document.getElementById('pdfFileInput');
                const uploadPlaceholder = document.getElementById('uploadPlaceholder');
                const uploadPreview = document.getElementById('uploadPreview');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const removeFileBtn = document.getElementById('removeFile');

                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Show file preview
                        uploadPlaceholder.classList.add('d-none');
                        uploadPreview.classList.remove('d-none');

                        // Update file info
                        fileName.textContent = file.name;
                        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                        fileSize.textContent = `${sizeMB} MB`;
                    }
                });

                removeFileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Reset file input
                    fileInput.value = '';

                    // Show placeholder
                    uploadPlaceholder.classList.remove('d-none');
                    uploadPreview.classList.add('d-none');
                });

                // Drag and drop styling
                const uploadArea = document.querySelector('.upload-area');

                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('border-primary', 'bg-light-primary');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-primary', 'bg-light-primary');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('border-primary', 'bg-light-primary');
                });
            });
        </script>
    @endpush

</x-default-layout>
