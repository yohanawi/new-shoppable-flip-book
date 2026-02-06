<x-default-layout>

    @section('title')
        Page Management - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.page-management', $pdf) }}
    @endsection

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-light-primary">Preview Flipbook</a>
            <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light btn-active-light-primary">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-7">

        <div class="col-lg-4">
            <div class="card shadow-sm mb-7">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title">Preview</h3>
                </div>
                <div class="card-body p-0" style="height: 55vh;">
                    <iframe src="{{ route('catalog.pdfs.source', $pdf) }}" style="border:0; width:100%; height:100%;"
                        title="{{ $pdf->title }}"></iframe>
                </div>
                <div class="card-footer">
                    <a href="{{ route('catalog.pdfs.preview', $pdf) }}" class="btn btn-light-primary w-100">Open
                        Flipbook Preview</a>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title">Replace PDF</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('catalog.pdfs.replace', $pdf) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label class="form-label required">Upload another PDF</label>
                            <input type="file" name="pdf" accept="application/pdf"
                                class="form-control form-control-solid @error('pdf') is-invalid @enderror" required>
                            @error('pdf')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">Replacing will re-initialize page list.</div>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">Replace PDF</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-7">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title">Download</h3>
                </div>
                <div class="card-body">
                    <div class="text-muted mb-4">Download reflects hidden/deleted/reordered pages.</div>
                    <a class="btn btn-light-primary w-100" href="{{ route('catalog.pdfs.download', $pdf) }}">Download
                        Managed PDF</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Edit Pages</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Rename, change order, lock, hide, delete</span>
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

                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th style="width: 90px;">Order</th>
                                            <th style="width: 120px;">Page #</th>
                                            <th>Page Title</th>
                                            <th style="width: 120px;">Hidden</th>
                                            <th style="width: 120px;">Locked</th>
                                            <th class="text-end" style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @forelse ($pages as $page)
                                            <tr>
                                                <td>
                                                    <input type="number" min="1"
                                                        class="form-control form-control-solid"
                                                        name="pages[{{ $page->id }}][display_order]"
                                                        value="{{ old('pages.' . $page->id . '.display_order', $page->display_order) }}">
                                                </td>
                                                <td>
                                                    <span class="badge badge-light">{{ $page->page_number }}</span>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-solid"
                                                        name="pages[{{ $page->id }}][title]"
                                                        value="{{ old('pages.' . $page->id . '.title', $page->title) }}">
                                                    @if ($page->is_locked)
                                                        <div class="form-text text-muted">Locked page (changes are
                                                            ignored
                                                            until unlocked)</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="pages[{{ $page->id }}][is_hidden]" value="1"
                                                            {{ old('pages.' . $page->id . '.is_hidden', $page->is_hidden) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="pages[{{ $page->id }}][is_locked]" value="1"
                                                            {{ old('pages.' . $page->id . '.is_locked', $page->is_locked) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button type="submit" class="btn btn-sm btn-light-danger"
                                                        form="delete-page-{{ $page->id }}"
                                                        {{ $page->is_locked ? 'disabled' : '' }}>
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

                        @foreach ($pages as $page)
                            <form id="delete-page-{{ $page->id }}"
                                action="{{ route('catalog.pdfs.pages.delete', [$pdf, $page]) }}" method="POST"
                                onsubmit="return confirm('Delete this page from the managed PDF?');"
                                style="display:none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
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
    @endif

</x-default-layout>
