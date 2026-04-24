<x-default-layout>

    @section('title')
        Page Management
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.page-management', $pdf) }}
    @endsection

    @php
        $previewVersion = max(
            (int) optional($pdf->updated_at)->getTimestamp(),
            (int) ($pages
                ->filter(fn($page) => $page->updated_at)
                ->max(fn($page) => $page->updated_at->getTimestamp()) ?? 0),
        );
        $livePreviewUrl = route('catalog.pdfs.file', [$pdf, 'v' => $previewVersion ?: now()->getTimestamp()]);
        $latestDownloadUrl = route('catalog.pdfs.download', [$pdf, 'v' => $previewVersion ?: now()->getTimestamp()]);
    @endphp

    <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center mb-8">
        <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light border">
            <i class="ki-outline ki-arrow-left fs-2"></i> Back
        </a>
        <div class="d-flex flex-wrap gap-3">
            <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-light-primary">
                Preview Flipbook
            </a>
            <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light-success">
                Download Managed PDF
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-7 align-items-start">

        <div class="col-lg-4">
            <div class="position-sticky top-0">
                <div class="card border-0 shadow-sm mb-7">
                    <div class="card-header border-0 pt-7 pb-3 px-7">
                        <div>
                            <span class="badge badge-light-primary mb-4">Live PDF Preview</span>
                            <h3 class="card-title fw-bold text-gray-900 mb-2">{{ $pdf->title }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0 px-7 pb-5">
                        <div class="row g-3 mb-6">
                            <div class="col-6 col-xl-4">
                                <div
                                    class="border border-dashed border-gray-300 rounded-3 bg-light-primary px-4 py-3 h-100">
                                    <span class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">
                                        Total Pages
                                    </span>
                                    <span class="text-gray-900 fw-bold fs-2">{{ $pages->count() }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-xl-4">
                                <div
                                    class="border border-dashed border-gray-300 rounded-3 bg-light-warning px-4 py-3 h-100">
                                    <span class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Hidden</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-2">{{ $pages->where('is_hidden', true)->count() }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-xl-4">
                                <div
                                    class="border border-dashed border-gray-300 rounded-3 bg-light-danger px-4 py-3 h-100">
                                    <span class="text-muted text-uppercase fw-semibold fs-8 d-block mb-1">Locked</span>
                                    <span
                                        class="text-gray-900 fw-bold fs-2">{{ $pages->where('is_locked', true)->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-4 overflow-hidden bg-light">
                            <div style="height: 78vh; min-height: 720px;">
                                <iframe src="{{ $livePreviewUrl }}" class="w-100 h-100"
                                    title="{{ $pdf->title }}"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Edit Pages</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">
                            Rename pages, drag to reorder, hide, lock, or mark pages for deletion.
                        </span>
                    </h3>
                </div>

                <div class="card-body pt-0">
                    @if ($pages->isEmpty())
                        <div class="alert alert-warning d-flex flex-column flex-sm-row p-5 mb-8">
                            <div class="d-flex flex-column">
                                <h5 class="mb-1">Pages not initialized</h5>
                                <span class="text-gray-700">
                                    Your PDF is uploaded, but page records were not created.
                                    Click <strong>Initialize Pages</strong> to generate pages using PDF.js (browser).
                                </span>
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

                            <div class="table-responsive border rounded-3">
                                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0 page-management-table">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-150px ps-3">Order</th>
                                            <th class="min-w-100px">Page #</th>
                                            <th>Page Title</th>
                                            <th class="w-125px">Hidden</th>
                                            <th class="w-125px">Locked</th>
                                            <th class="text-end w-125px pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold" id="page-management-table">
                                        @forelse ($pages as $page)
                                            <tr data-page-row data-page-id="{{ $page->id }}" data-drag-ready="0">
                                                <td class="min-w-150px ps-5">
                                                    <input type="hidden" name="pages[{{ $page->id }}][is_deleted]"
                                                        value="0" class="delete-flag-input">
                                                    <input type="hidden" class="order-input"
                                                        name="pages[{{ $page->id }}][display_order]"
                                                        value="{{ old('pages.' . $page->id . '.display_order', $page->display_order) }}">
                                                    <div class="d-inline-flex align-items-center gap-3">
                                                        <button type="button"
                                                            class="btn btn-icon btn-light btn-active-light-primary drag-handle w-40px h-40px"
                                                            aria-label="Drag page to reorder">
                                                            <i class="bi bi-grip-vertical fs-3 text-gray-500"></i>
                                                        </button>
                                                        <span
                                                            class="badge badge-light-primary d-inline-flex align-items-center justify-content-center w-40px h-40px"
                                                            data-order-label>
                                                            {{ old('pages.' . $page->id . '.display_order', $page->display_order) }}
                                                        </span>
                                                    </div>
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
                                                            ignored until unlocked)</div>
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
                                                <td class="text-end pe-5">
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

                            <div class="d-flex justify-content-end mt-5">
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
                            const orderLabel = row.querySelector('[data-order-label]');
                            const deleteFlagInput = row.querySelector('.delete-flag-input');
                            if (!orderInput || !deleteFlagInput) {
                                return;
                            }

                            if (deleteFlagInput.value === '1') {
                                return;
                            }

                            orderInput.value = String(nextOrder);

                            if (orderLabel) {
                                orderLabel.textContent = String(nextOrder);
                            }

                            nextOrder++;
                        });
                    };

                    const hasBlockingRowBetween = (draggedCandidate, targetCandidate) => {
                        const rows = getRows();
                        const startIndex = rows.indexOf(draggedCandidate);
                        const endIndex = rows.indexOf(targetCandidate);

                        if (startIndex === -1 || endIndex === -1) {
                            return true;
                        }

                        const lower = Math.min(startIndex, endIndex);
                        const upper = Math.max(startIndex, endIndex);

                        for (let index = lower + 1; index < upper; index++) {
                            const row = rows[index];
                            const lockCheckbox = row.querySelector('.lock-checkbox');
                            const deleteFlagInput = row.querySelector('.delete-flag-input');

                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1') {
                                return true;
                            }
                        }

                        return false;
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
                        const dragHandle = row.querySelector('.drag-handle');
                        const deleteButton = row.querySelector('.toggle-delete-btn');

                        if (!lockCheckbox || !deleteFlagInput || !titleInput || !dragHandle || !deleteButton) {
                            return;
                        }

                        const locked = lockCheckbox.checked;
                        const deleted = deleteFlagInput.value === '1';

                        row.classList.remove('bg-light-primary', 'bg-light', 'opacity-75');
                        row.classList.toggle('opacity-50', deleted);
                        row.classList.toggle('text-decoration-line-through', deleted);
                        row.classList.toggle('bg-light-primary', draggedRow === row);
                        row.classList.toggle('bg-light', locked || deleted);
                        row.classList.toggle('opacity-75', locked && !deleted);
                        row.draggable = !locked && !deleted;

                        titleInput.readOnly = locked || deleted;
                        dragHandle.disabled = locked || deleted;
                        row.dataset.dragReady = '0';

                        deleteButton.textContent = deleted ? 'Undo' : 'Delete';
                        deleteButton.classList.toggle('btn-light-danger', !deleted);
                        deleteButton.classList.toggle('btn-light-warning', deleted);

                        renderStatus(row, locked, deleted);
                    };

                    getRows().forEach((row) => {
                        const lockCheckbox = row.querySelector('.lock-checkbox');
                        const hiddenCheckbox = row.querySelector('.hidden-checkbox');
                        const deleteFlagInput = row.querySelector('.delete-flag-input');
                        const dragHandle = row.querySelector('.drag-handle');
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

                        const armDrag = () => {
                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1') {
                                row.dataset.dragReady = '0';
                                return;
                            }

                            row.dataset.dragReady = '1';
                        };

                        const disarmDrag = () => {
                            row.dataset.dragReady = '0';
                        };

                        dragHandle?.addEventListener('pointerdown', armDrag);
                        dragHandle?.addEventListener('keydown', (event) => {
                            if (event.key === ' ' || event.key === 'Enter') {
                                armDrag();
                            }
                        });
                        dragHandle?.addEventListener('pointerup', disarmDrag);
                        dragHandle?.addEventListener('pointercancel', disarmDrag);
                        dragHandle?.addEventListener('blur', disarmDrag);

                        row.addEventListener('dragstart', (event) => {
                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1') {
                                event.preventDefault();
                                return;
                            }

                            if (row.dataset.dragReady !== '1') {
                                event.preventDefault();
                                return;
                            }

                            draggedRow = row;
                            event.dataTransfer?.setData('text/plain', row.dataset.pageId || '');
                            if (event.dataTransfer) {
                                event.dataTransfer.effectAllowed = 'move';
                            }
                            row.classList.add('bg-light-primary');
                        });

                        row.addEventListener('dragend', () => {
                            row.classList.remove('bg-light-primary');
                            draggedRow = null;
                            disarmDrag();
                            syncOrderInputs();
                        });

                        row.addEventListener('dragover', (event) => {
                            if (!draggedRow || draggedRow === row) {
                                return;
                            }

                            if (lockCheckbox?.checked || deleteFlagInput?.value === '1' ||
                                hasBlockingRowBetween(
                                    draggedRow,
                                    row,
                                )) {
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
