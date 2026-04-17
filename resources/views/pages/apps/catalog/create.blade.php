<x-default-layout>

    @section('title')
        Upload PDF
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.create') }}
    @endsection

    @if ($errors->has('billing'))
        <div class="alert alert-danger d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-information-5 fs-2 text-danger mt-1"></i>
            <div>
                <div class="fw-bold mb-1">Upload blocked</div>
                <div>{{ $errors->first('billing') }}</div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>
                <div class="fw-bold mb-1">Ready to upload</div>
                <div>{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if ($errors->any() && !$errors->has('billing'))
        <div class="alert alert-danger d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-cross-circle fs-2 text-danger mt-1"></i>
            <div>
                <div class="fw-bold mb-1">Upload form has errors</div>
                <div>Please review the highlighted fields and try again.</div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm overflow-hidden mb-8">
        <div class="card-body p-0">
            <div class="p-10 p-lg-15" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
                <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center">
                    <div class="mw-600px">
                        <span class="badge badge-light-primary mb-4">Step 1 of 2</span>
                        <h1 class="text-white fw-bold mb-4">Upload the PDF first</h1>
                        <div class="text-white opacity-75 fs-5">
                            This page only handles the PDF upload. After upload, go to Catalog PDFs to open the file
                            and use Page Management, Flip Physics, and Slicer on the same PDF.
                        </div>
                    </div>

                    <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light-primary">
                        <i class="ki-outline ki-left fs-4 me-1"></i>
                        Catalog PDFs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-8 align-items-start">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-8">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Upload details</h3>
                        <div class="text-muted">Enter the PDF title, description, file, and visibility.</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form action="{{ route('catalog.pdfs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-8">
                            <label class="form-label fw-bold text-gray-900 required">PDF Title</label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                class="form-control form-control-lg form-control-solid @error('title') is-invalid @enderror"
                                placeholder="Example: Spring Collection Catalog" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-8">
                            <label class="form-label fw-bold text-gray-900">Description</label>
                            <textarea name="description" rows="4"
                                class="form-control form-control-lg form-control-solid @error('description') is-invalid @enderror"
                                placeholder="Optional notes about this PDF">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-8">
                            <label class="form-label fw-bold text-gray-900 required">PDF File</label>
                            <div
                                class="border-2 border-dashed rounded-4 p-8 p-lg-10 text-center bg-light-primary position-relative @error('pdf') border-danger @enderror">
                                <input type="file" name="pdf" accept="application/pdf" id="pdfFileInput"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                    style="cursor: pointer;" required>

                                <div id="uploadPlaceholder">
                                    <i class="ki-outline ki-file-up fs-4x text-primary mb-4"></i>
                                    <div class="fw-bold text-gray-900 fs-4 mb-2">Choose a PDF to upload</div>
                                    <div class="text-muted">PDF only, maximum 20MB</div>
                                </div>

                                <div id="uploadPreview" class="d-none">
                                    <i class="ki-outline ki-file-sheet fs-4x text-success mb-4"></i>
                                    <div class="fw-bold text-gray-900 fs-4 mb-1" id="fileName"></div>
                                    <div class="text-muted mb-4" id="fileSize"></div>
                                    <button type="button" class="btn btn-sm btn-light-danger" id="removeFile">
                                        Remove file
                                    </button>
                                </div>
                            </div>
                            @error('pdf')
                                <div class="text-danger fw-semibold fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-10">
                            <label class="form-label fw-bold text-gray-900 required">Visibility</label>
                            <select name="visibility"
                                class="form-select form-select-lg form-select-solid @error('visibility') is-invalid @enderror"
                                data-control="select2" data-hide-search="true" required>
                                @foreach ($visibilityOptions as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('visibility', \App\Models\CatalogPdf::VISIBILITY_PRIVATE) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">If you choose public, anyone with the share link can view
                                the edited PDF.</div>
                            @error('visibility')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                            <div class="text-muted fs-7">
                                After upload, continue from the Catalog PDFs workflow screen.
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg px-8">
                                <i class="ki-outline ki-cloud-add fs-3 me-2"></i>
                                Upload PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-8 p-lg-10">
                    <h3 class="fw-bold text-gray-900 mb-4">What happens after upload?</h3>
                    <div class="d-flex flex-column gap-5">
                        <div class="d-flex gap-4">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-outline ki-notepad text-primary fs-3"></i>
                                </span>
                            </span>
                            <div>
                                <div class="fw-bold text-gray-900">Page Management</div>
                                <div class="text-muted fs-7">Reorder pages, rename them, hide them, or lock them.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-4">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-outline ki-rocket text-info fs-3"></i>
                                </span>
                            </span>
                            <div>
                                <div class="fw-bold text-gray-900">Flip Physics</div>
                                <div class="text-muted fs-7">Tune the viewer while still using the same managed PDF.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-4">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-outline ki-shop text-success fs-3"></i>
                                </span>
                            </span>
                            <div>
                                <div class="fw-bold text-gray-900">Slicer</div>
                                <div class="text-muted fs-7">Create interactive hotspots and shoppable areas on that
                                    same PDF.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const fileInput = document.getElementById('pdfFileInput');
                const uploadPlaceholder = document.getElementById('uploadPlaceholder');
                const uploadPreview = document.getElementById('uploadPreview');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const removeFile = document.getElementById('removeFile');

                if (!fileInput || !uploadPlaceholder || !uploadPreview || !fileName || !fileSize || !removeFile) {
                    return;
                }

                const formatBytes = (bytes) => {
                    if (!bytes) {
                        return '0 KB';
                    }

                    const units = ['bytes', 'KB', 'MB', 'GB'];
                    let value = bytes;
                    let unitIndex = 0;

                    while (value >= 1024 && unitIndex < units.length - 1) {
                        value /= 1024;
                        unitIndex++;
                    }

                    return `${value.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
                };

                fileInput.addEventListener('change', () => {
                    const [file] = fileInput.files || [];

                    if (!file) {
                        uploadPlaceholder.classList.remove('d-none');
                        uploadPreview.classList.add('d-none');
                        fileName.textContent = '';
                        fileSize.textContent = '';
                        return;
                    }

                    uploadPlaceholder.classList.add('d-none');
                    uploadPreview.classList.remove('d-none');
                    fileName.textContent = file.name;
                    fileSize.textContent = formatBytes(file.size);
                });

                removeFile.addEventListener('click', () => {
                    fileInput.value = '';
                    uploadPlaceholder.classList.remove('d-none');
                    uploadPreview.classList.add('d-none');
                    fileName.textContent = '';
                    fileSize.textContent = '';
                });
            })();
        </script>
    @endpush

</x-default-layout>
