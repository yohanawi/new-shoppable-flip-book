<x-default-layout>

    @section('title')
        Flip Physics
    @endsection

    <style>
        #flipbook {
            margin: 0 auto;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        #flipbook canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .flipbook-status {
            color: #7e8299;
        }
    </style>

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div>
            <h3 class="fw-bold my-2">Flip Physics Template</h3>
            <div class="text-muted">{{ $pdf->title }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light btn-active-light-primary">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-7">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title">Settings</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('catalog.pdfs.flip-physics.update', $pdf) }}" method="POST"
                        id="flipPhysicsForm">
                        @csrf

                        <div class="mb-6">
                            <label class="form-label required">Preset</label>
                            <select class="form-select form-select-solid" name="preset" id="preset"
                                data-control="select2" data-hide-search="true">
                                @foreach ($presetOptions as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('preset', $setting->preset) === $key ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Flip Duration (ms)</label>
                            <input type="number" min="200" max="4000" class="form-control form-control-solid"
                                name="duration_ms" id="duration_ms"
                                value="{{ old('duration_ms', $setting->duration_ms) }}">
                            <div class="form-text text-muted">Lower = faster, higher = smoother.</div>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Elevation (shadow depth)</label>
                            <input type="range" min="0" max="100" class="form-range" name="elevation"
                                id="elevation" value="{{ old('elevation', $setting->elevation) }}">
                            <div class="d-flex justify-content-between text-muted fs-7">
                                <span>0</span>
                                <span id="elevationValue">{{ old('elevation', $setting->elevation) }}</span>
                                <span>100</span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Display Mode</label>
                            <select class="form-select form-select-solid" name="display_mode" id="display_mode"
                                data-control="select2" data-hide-search="true">
                                <option value="auto"
                                    {{ old('display_mode', $setting->display_mode) === 'auto' ? 'selected' : '' }}>Auto
                                </option>
                                <option value="single"
                                    {{ old('display_mode', $setting->display_mode) === 'single' ? 'selected' : '' }}>
                                    Single</option>
                                <option value="double"
                                    {{ old('display_mode', $setting->display_mode) === 'double' ? 'selected' : '' }}>
                                    Double</option>
                            </select>
                            <div class="form-text text-muted">Auto uses single on mobile, double on desktop.</div>
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Render Quality</label>
                            <input type="number" min="80" max="200" class="form-control form-control-solid"
                                name="render_scale_percent" id="render_scale_percent"
                                value="{{ old('render_scale_percent', $setting->render_scale_percent) }}">
                            <div class="form-text text-muted">Higher looks sharper but renders slower.</div>
                        </div>

                        <div class="mb-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="gradients" id="gradients"
                                    value="1" {{ old('gradients', $setting->gradients) ? 'checked' : '' }}>
                                <label class="form-check-label" for="gradients">Gradients</label>
                            </div>

                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="acceleration" id="acceleration"
                                    value="1" {{ old('acceleration', $setting->acceleration) ? 'checked' : '' }}>
                                <label class="form-check-label" for="acceleration">Acceleration</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" id="btnApply">Apply to Preview</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-7">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('catalog.pdfs.flip-physics.preview', $pdf) }}"
                        class="btn btn-light-info w-100 mb-3" target="_blank">
                        <i class="bi bi-fullscreen"></i> Open Preview (New Tab)
                    </a>
                    <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light-primary w-100">
                        <i class="bi bi-download"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Live Preview</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Turn.js + PDF.js</span>
                    </h3>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
                        <div class="flipbook-status" id="status">Rendering pages…</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light-info btn-sm" id="btnFullscreen"
                                title="Open in new tab">
                                <i class="bi bi-fullscreen"></i> Fullscreen
                            </button>
                            <button type="button" class="btn btn-light-success btn-sm" id="btnShare"
                                title="Share">
                                <i class="bi bi-share"></i> Share
                            </button>
                            <button type="button" class="btn btn-light" id="btnPrev">Previous</button>
                            <button type="button" class="btn btn-light" id="btnNext">Next</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <div id="flipbook"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Flip Physics Flipbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Share this link to allow others to view the flipbook with current settings:
                    </p>
                    <div class="share-link-container">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.flip-physics.share', $pdf) }}">
                        <button type="button" class="btn btn-primary" id="btnCopyLink">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="mt-3" id="copySuccess" style="display:none;">
                        <div class="alert alert-success">Link copied to clipboard!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .share-link-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .share-link-container input {
            flex: 1;
        }
    </style>

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                const statusEl = document.getElementById('status');
                const flipbookEl = document.getElementById('flipbook');

                const form = document.getElementById('flipPhysicsForm');
                const elevationInput = document.getElementById('elevation');
                const elevationValue = document.getElementById('elevationValue');

                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function setStatus(text) {
                    statusEl.textContent = text;
                }

                function readSettings() {
                    const fd = new FormData(form);
                    const displayMode = String(fd.get('display_mode') || 'auto');

                    const isMobile = window.innerWidth < 992;
                    const display = (displayMode === 'auto') ? (isMobile ? 'single' : 'double') : displayMode;

                    return {
                        duration: Number(fd.get('duration_ms') || 900),
                        gradients: fd.get('gradients') === '1',
                        acceleration: fd.get('acceleration') === '1',
                        elevation: Number(fd.get('elevation') || 50),
                        renderScale: Number(fd.get('render_scale_percent') || 120) / 100,
                        display,
                    };
                }

                function buildPageShells(pageCount) {
                    flipbookEl.innerHTML = '';
                    for (let i = 1; i <= pageCount; i++) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page';
                        pageDiv.dataset.pageNumber = String(i);

                        const canvas = document.createElement('canvas');
                        canvas.width = 10;
                        canvas.height = 10;
                        pageDiv.appendChild(canvas);

                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function destroyTurnIfExists() {
                    try {
                        const $flipbook = $('#flipbook');
                        if ($flipbook.data('turn')) {
                            $flipbook.turn('destroy');
                        }
                    } catch (e) {}
                }

                async function renderAll() {
                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        setStatus('Turn.js failed to load — pages will show as a list.');
                        return;
                    }

                    setStatus('Loading PDF…');

                    let pdf;
                    try {
                        pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    } catch (e) {
                        console.error(e);
                        setStatus('Failed to load PDF. Check: ' + pdfUrl);
                        return;
                    }

                    const cfg = readSettings();
                    const pageCount = pdf.numPages || 0;

                    if (pageCount <= 0) {
                        setStatus('No pages found in PDF.');
                        return;
                    }

                    destroyTurnIfExists();
                    buildPageShells(pageCount);

                    // Size based on first page
                    const first = await pdf.getPage(1);
                    const rawViewport = first.getViewport({
                        scale: 1
                    });

                    const containerWidth = Math.min(1200, document.getElementById('kt_app_content_container')
                        ?.clientWidth || window.innerWidth);
                    const targetSingleWidth = Math.min(520, Math.max(320, containerWidth * 0.45));

                    const scaleToFit = (targetSingleWidth / rawViewport.width) * cfg.renderScale;
                    const w = Math.floor(rawViewport.width * scaleToFit);
                    const h = Math.floor(rawViewport.height * scaleToFit);

                    const pageDivs = flipbookEl.querySelectorAll('.page');

                    for (let i = 1; i <= pageCount; i++) {
                        setStatus(`Rendering page ${i} of ${pageCount}…`);
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({
                            scale: scaleToFit
                        });

                        const pageDiv = pageDivs[i - 1];
                        pageDiv.style.width = w + 'px';
                        pageDiv.style.height = h + 'px';

                        const canvas = pageDiv.querySelector('canvas');
                        const ctx = canvas.getContext('2d');

                        canvas.width = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);

                        await page.render({
                            canvasContext: ctx,
                            viewport
                        }).promise;
                    }

                    setStatus('Ready');

                    const $flipbook = $('#flipbook');
                    flipbookEl.style.width = (cfg.display === 'double' ? w * 2 : w) + 'px';
                    flipbookEl.style.height = h + 'px';
                    $flipbook.turn({
                        width: cfg.display === 'double' ? w * 2 : w,
                        height: h,
                        autoCenter: true,
                        display: cfg.display,
                        duration: cfg.duration,
                        acceleration: cfg.acceleration,
                        gradients: cfg.gradients,
                        elevation: cfg.elevation,
                    });

                    document.getElementById('btnPrev').onclick = () => $flipbook.turn('previous');
                    document.getElementById('btnNext').onclick = () => $flipbook.turn('next');
                }

                document.getElementById('btnApply').addEventListener('click', () => {
                    renderAll();
                });

                elevationInput.addEventListener('input', () => {
                    elevationValue.textContent = elevationInput.value;
                });

                // Fullscreen functionality - opens in new tab
                document.getElementById('btnFullscreen').addEventListener('click', function() {
                    window.open(@json(route('catalog.pdfs.flip-physics.preview', $pdf)), '_blank');
                });

                // Share functionality
                document.getElementById('btnShare').addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                    shareModal.show();
                });

                document.getElementById('btnCopyLink').addEventListener('click', function() {
                    const linkInput = document.getElementById('shareLink');
                    linkInput.select();
                    linkInput.setSelectionRange(0, 99999); // For mobile

                    navigator.clipboard.writeText(linkInput.value).then(function() {
                        document.getElementById('copySuccess').style.display = 'block';
                        setTimeout(function() {
                            document.getElementById('copySuccess').style.display = 'none';
                        }, 3000);
                    }).catch(function(err) {
                        alert('Failed to copy link: ' + err);
                    });
                });

                // Initial render
                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
