<x-default-layout>

    @section('title')
        Page Management
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.page-management', $pdf) }}
    @endsection

    <div class="card border-0 shadow-sm overflow-hidden mb-8">
        <div class="card-body p-0">
            <div class="p-10 p-lg-15" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
                <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center">
                    <div class="mw-500px">
                        <span class="badge badge-light-primary mb-4">Page Management</span>
                        <h1 class="text-white fw-bold mb-4">Manage pages for {{ $pdf->title }}</h1>
                        <div class="text-white opacity-75 fs-5">
                            This screen is for managing the PDF pages only. Reorder pages, rename them, hide them,
                            lock them, or replace the uploaded PDF. These changes also feed the same PDF used by Flip
                            Physics and Slicer.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light">
                            Back
                        </a>
                        <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-light-primary">
                            Preview Flipbook
                        </a>
                        <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light-success">
                            Download Managed PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-6 g-xl-8 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Current function</div>
                    <div class="fw-bold text-gray-900 fs-3">Page Management</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Pages</div>
                    <div class="fw-bold text-gray-900 fs-3">{{ $pages->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Visibility</div>
                    <div class="fw-bold text-gray-900 fs-3 text-capitalize">{{ $pdf->visibility }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Original file</div>
                    <div class="fw-bold text-gray-900 fs-6">{{ $pdf->original_filename ?: 'Uploaded PDF' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-7">

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-7">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title fw-bold text-gray-900">PDF Preview</h3>
                </div>
                <div class="card-body p-0" style="height: 55vh;">
                    <iframe src="{{ route('catalog.pdfs.file', $pdf) }}" style="border:0; width:100%; height:100%;"
                        title="{{ $pdf->title }}"></iframe>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-7 px-7">
                    <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-light-primary w-100">Open
                        Flipbook Preview</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-7">
                <div class="card-header border-0 pt-7">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Download</h3>
                        <div class="text-muted fs-7">Download includes page order, hidden pages, and deletes.</div>
                    </div>
                </div>
                <div class="card-body">
                    <a class="btn btn-light-primary w-100" href="{{ route('catalog.pdfs.download', $pdf) }}">Download
                        Managed PDF</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Edit Pages</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Rename pages, change order, hide, lock, or mark
                            pages for deletion.</span>
                    </h3>
                </div>

                <div class="card-body pt-0">
                    @if ($pages->isEmpty())
                        <div class="alert alert-warning d-flex flex-column flex-sm-row p-5 mb-8">
                            <div class="d-flex flex-column">
                                <h5 class="mb-1">Pages not initialized</h5>
                                <span class="text-gray-700">Your PDF is uploaded, but page records were not created.
                                    Click <strong>Initialize Pages</strong> to generate pages using PDF.js
                                    (browser).</span>
                            </div>
                        </div>

                        <form id="init-pages-form" action="{{ route('catalog.pdfs.pages.init', $pdf) }}"
                            method="POST">
                            @csrf
                            <input type="hidden" name="page_count" id="page_count" value="">
                            <button type="button" class="btn btn-primary" id="btnInitPages">Initialize Pages</button>
                        </form>
                    @else
                        <form action="{{ route('catalog.pdfs.manage.update', $pdf) }}" method="POST">
                            @csrf

                            @if ($errors->any())
                                <div class="alert alert-danger d-flex flex-column gap-2 mb-8">
                                    <strong>Unable to save page changes.</strong>
                                    <ul class="mb-0 ps-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th style="width: 120px;">Order</th>
                                            <th style="width: 120px;">Page #</th>
                                            <th>Page Title</th>
                                            <th style="width: 120px;">Hidden</th>
                                            <th style="width: 120px;">Locked</th>
                                            <th class="text-end" style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold" id="page-management-table">
                                        @forelse ($pages as $page)
                                            <tr data-page-row data-page-id="{{ $page->id }}">
                                                <td>
                                                    <input type="hidden" name="pages[{{ $page->id }}][is_deleted]"
                                                        value="0" class="delete-flag-input">
                                                    <input type="number" min="1"
                                                        class="form-control form-control-solid order-input"
                                                        name="pages[{{ $page->id }}][display_order]"
                                                        value="{{ old('pages.' . $page->id . '.display_order', $page->display_order) }}">
                                                    <div class="form-text text-muted">Drag row to reorder</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light">{{ $page->page_number }}</span>
                                                </td>
                                                <td>
                                                    <input type="text"
                                                        class="form-control form-control-solid title-input"
                                                        name="pages[{{ $page->id }}][title]"
                                                        value="{{ old('pages.' . $page->id . '.title', $page->title) }}">
                                                    @if ($page->is_locked)
                                                        <div class="form-text text-muted">Locked page (changes are
                                                            ignored
                                                            until unlocked)</div>
                                                    @endif
                                                    <div class="mt-2 row-status"></div>
                                                </td>
                                                <td>
                                                    <input type="hidden"
                                                        name="pages[{{ $page->id }}][is_hidden]" value="0">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input hidden-checkbox"
                                                            type="checkbox"
                                                            name="pages[{{ $page->id }}][is_hidden]"
                                                            value="1"
                                                            {{ old('pages.' . $page->id . '.is_hidden', $page->is_hidden) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="hidden"
                                                        name="pages[{{ $page->id }}][is_locked]" value="0">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input lock-checkbox" type="checkbox"
                                                            name="pages[{{ $page->id }}][is_locked]"
                                                            value="1"
                                                            {{ old('pages.' . $page->id . '.is_locked', $page->is_locked) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-light-danger toggle-delete-btn">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-10">
                                                    <div class="text-muted">No pages found.</div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @if ($pages->isEmpty())
        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
            <script>
                (function() {
                    const btn = document.getElementById('btnInitPages');
                    const form = document.getElementById('init-pages-form');
                    const pageCountInput = document.getElementById('page_count');
                    const pdfUrl = @json(route('catalog.pdfs.source', $pdf));

                    if (!btn || !form || !pageCountInput) return;

                    if (window.pdfjsLib) {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                    }

                    btn.addEventListener('click', async () => {
                        btn.disabled = true;
                        btn.textContent = 'Initializing…';
                        try {
                            const doc = await window.pdfjsLib.getDocument(pdfUrl).promise;
                            pageCountInput.value = String(doc.numPages || 0);
                            if (!pageCountInput.value || pageCountInput.value === '0') {
                                throw new Error('Unable to detect page count');
                            }
                            form.submit();
                        } catch (e) {
                            console.error(e);
                            btn.disabled = false;
                            btn.textContent = 'Initialize Pages';
                            alert(
                                'Failed to initialize pages. Please ensure the PDF loads in the Preview iframe and try again.'
                            );
                        }
                    });
                })();
            </script>
        @endpush
    @else
        @push('scripts')
            <script>
                (function() {
                    const tableBody = document.getElementById('page-management-table');
                    if (!tableBody) {
                        return;
                    }

                    let draggedRow = null;

                    const getRows = () => Array.from(tableBody.querySelectorAll('[data-page-row]'));

                    const syncOrderInputs = () => {
                        let nextOrder = 1;

                        getRows().forEach((row) => {
                            const orderInput = row.querySelector('.order-input');
                            const deleteFlagInput = row.querySelector('.delete-flag-input');
                            if (!orderInput || !deleteFlagInput) {
                                return;
                            }

                            if (deleteFlagInput.value === '1') {
                                return;
                            }

                            orderInput.value = String(nextOrder++);
                        });
                    };

                    const renderStatus = (row, locked, deleted) => {
                        const hiddenCheckbox = row.querySelector('.hidden-checkbox');
                        const statusContainer = row.querySelector('.row-status');
                        if (!statusContainer || !hiddenCheckbox) {
                            return;
                        }

                        const badges = [];
                        if (deleted) {
                            badges.push('<span class="badge badge-light-danger me-2">Marked for deletion</span>');
                        }
                        if (locked) {
                            badges.push('<span class="badge badge-light-warning me-2">Locked</span>');
                        }
                        if (hiddenCheckbox.checked) {
                            badges.push('<span class="badge badge-light-dark">Hidden</span>');
                        }

                        statusContainer.innerHTML = badges.join('');
                    };

                    const applyRowState = (row) => {
                        const lockCheckbox = row.querySelector('.lock-checkbox');
                        const deleteFlagInput = row.querySelector('.delete-flag-input');
                        const titleInput = row.querySelector('.title-input');
                        const orderInput = row.querySelector('.order-input');
                        const deleteButton = row.querySelector('.toggle-delete-btn');

                        if (!lockCheckbox || !deleteFlagInput || !titleInput || !orderInput || !deleteButton) {
                            return;
                        }

                        const locked = lockCheckbox.checked;
                        const deleted = deleteFlagInput.value === '1';

                        row.classList.toggle('opacity-50', deleted);
                        row.classList.toggle('text-decoration-line-through', deleted);
                        row.draggable = !locked && !deleted;

                        titleInput.readOnly = locked || deleted;
                        orderInput.readOnly = locked || deleted;

                        deleteButton.textContent = deleted ? 'Undo' : 'Delete';
                        deleteButton.classList.toggle('btn-light-danger', !deleted);
                        deleteButton.classList.toggle('btn-light-warning', deleted);

                        renderStatus(row, locked, deleted);
                    };

                    getRows().forEach((row) => {
                        const lockCheckbox = row.querySelector('.lock-checkbox');
                        const hiddenCheckbox = row.querySelector('.hidden-checkbox');
                        const deleteFlagInput = row.querySelector('.delete-flag-input');
                        const deleteButton = row.querySelector('.toggle-delete-btn');

                        applyRowState(row);

                        lockCheckbox?.addEventListener('change', () => {
                            applyRowState(row);
                            syncOrderInputs();
                        });

                        hiddenCheckbox?.addEventListener('click', (event) => {
                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1') {
                                event.preventDefault();
                            }
                        });

                        hiddenCheckbox?.addEventListener('change', () => {
                            applyRowState(row);
                        });

                        deleteButton?.addEventListener('click', () => {
                            if (lockCheckbox?.checked && deleteFlagInput?.value !== '1') {
                                return;
                            }

                            deleteFlagInput.value = deleteFlagInput.value === '1' ? '0' : '1';
                            applyRowState(row);
                            syncOrderInputs();
                        });

                        row.addEventListener('dragstart', (event) => {
                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1') {
                                event.preventDefault();
                                return;
                            }

                            draggedRow = row;
                            row.classList.add('bg-light-primary');
                        });

                        row.addEventListener('dragend', () => {
                            row.classList.remove('bg-light-primary');
                            draggedRow = null;
                            syncOrderInputs();
                        });

                        row.addEventListener('dragover', (event) => {
                            if (!draggedRow || draggedRow === row) {
                                return;
                            }

                            event.preventDefault();
                            const rowRect = row.getBoundingClientRect();
                            const shouldInsertBefore = event.clientY < rowRect.top + (rowRect.height / 2);
                            tableBody.insertBefore(draggedRow, shouldInsertBefore ? row : row.nextSibling);
                        });
                    });

                    syncOrderInputs();
                })();
            </script>
        @endpush
    @endif

</x-default-layout>
