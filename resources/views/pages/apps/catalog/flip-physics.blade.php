<x-default-layout>

    @section('title')
        Flip Physics
    @endsection

    <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center mb-5">
        <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light border">
            <i class="ki-outline ki-arrow-left fs-2"></i> Back
        </a>
        <div class="d-flex flex-wrap gap-3">
            <a href="{{ route('catalog.pdfs.flip-physics.preview', $pdf) }}" class="btn btn-light-primary"
                target="_blank">Preview</a>
            <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light-success">
                Download PDF
            </a>
        </div>
    </div>


    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Please fix the highlighted Flip Physics settings.</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-7">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Live Preview</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Preview the current flip settings before
                            saving
                            them.</span>
                    </h3>
                </div>

                <div class="card-body">
                    <div
                        class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4 mb-5">
                        <div class="text-muted fs-7 fw-semibold" id="status">Rendering pages…</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-light-info btn-sm" id="btnFullscreen"
                                title="Open in new tab">
                                <i class="bi bi-fullscreen"></i> Fullscreen
                            </button>
                            <button type="button" class="btn btn-light-success btn-sm" id="btnShare" title="Share">
                                <i class="bi bi-share"></i> Share
                            </button>
                            <button type="button" class="btn btn-light" id="btnPrev">Previous</button>
                            <button type="button" class="btn btn-light" id="btnNext">Next</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center align-items-center bg-light-primary rounded-4 border border-dashed border-primary p-4 p-xl-6 overflow-auto mh-750px"
                        id="flipbookStage">
                        <div id="flipbook"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pt-7">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Viewer Settings</h3>
                        <div class="text-muted fs-7">Apply changes to the live preview, then save them.</div>
                    </div>
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
                            @error('preset')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Flip Duration (ms)</label>
                            <input type="number" min="200" max="4000" class="form-control form-control-solid"
                                name="duration_ms" id="duration_ms"
                                value="{{ old('duration_ms', $setting->duration_ms) }}">
                            <div class="form-text text-muted">Lower = faster, higher = smoother.</div>
                            @error('duration_ms')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
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
                            @error('elevation')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Display Mode</label>
                            <select class="form-select form-select-solid" name="display_mode" id="display_mode"
                                data-control="select2" data-hide-search="true">
                                <option value="auto"
                                    {{ old('display_mode', $setting->display_mode) === 'auto' ? 'selected' : '' }}>
                                    Auto
                                </option>
                                <option value="single"
                                    {{ old('display_mode', $setting->display_mode) === 'single' ? 'selected' : '' }}>
                                    Single</option>
                                <option value="double"
                                    {{ old('display_mode', $setting->display_mode) === 'double' ? 'selected' : '' }}>
                                    Double</option>
                            </select>
                            <div class="form-text text-muted">Auto uses single on mobile, double on desktop.</div>
                            @error('display_mode')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="form-label required">Render Quality</label>
                            <input type="number" min="80" max="200"
                                class="form-control form-control-solid" name="render_scale_percent"
                                id="render_scale_percent"
                                value="{{ old('render_scale_percent', $setting->render_scale_percent) }}">
                            <div class="form-text text-muted">Higher looks sharper but renders slower.</div>
                            @error('render_scale_percent')
                                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="gradients" id="gradients"
                                    value="1" {{ old('gradients', $setting->gradients) ? 'checked' : '' }}>
                                <label class="form-check-label" for="gradients">Gradients</label>
                            </div>

                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="acceleration"
                                    id="acceleration" value="1"
                                    {{ old('acceleration', $setting->acceleration) ? 'checked' : '' }}>
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

            <div class="card border-0 shadow-sm mt-7">
                <div class="card-header border-0 pt-7">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Actions</h3>
                        <div class="text-muted fs-7">Preview, share, or download the current PDF.</div>
                    </div>
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
                    <p class="text-muted">Share this link to allow others to view the flipbook with current
                        settings:
                    </p>
                    <div class="input-group mt-4">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.flip-physics.share', $pdf) }}">
                        <button type="button" class="btn btn-primary" id="btnCopyLink">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="mt-3 d-none" id="copySuccess">
                        <div class="alert alert-success">Link copied to clipboard!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                const presetDefaults = @json($presetDefaults);
                const hasOldPreset = @json(old('preset') !== null);
                const statusEl = document.getElementById('status');
                const flipbookStageEl = document.getElementById('flipbookStage');
                const flipbookEl = document.getElementById('flipbook');
                const prevButton = document.getElementById('btnPrev');
                const nextButton = document.getElementById('btnNext');

                const form = document.getElementById('flipPhysicsForm');
                const presetInput = document.getElementById('preset');
                const durationInput = document.getElementById('duration_ms');
                const elevationInput = document.getElementById('elevation');
                const elevationValue = document.getElementById('elevationValue');
                const displayModeInput = document.getElementById('display_mode');
                const renderScaleInput = document.getElementById('render_scale_percent');
                const gradientsInput = document.getElementById('gradients');
                const accelerationInput = document.getElementById('acceleration');
                const previewFields = [
                    presetInput,
                    durationInput,
                    elevationInput,
                    displayModeInput,
                    renderScaleInput,
                    gradientsInput,
                    accelerationInput,
                ];

                let flipbookInstance = null;
                let renderGeneration = 0;
                let renderTimer = null;
                let keyboardBound = false;

                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function setStatus(text) {
                    statusEl.textContent = text;
                }

                function setNavigationEnabled(enabled) {
                    prevButton.disabled = !enabled;
                    nextButton.disabled = !enabled;
                }

                function syncSelect(selectElement) {
                    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
                        window.jQuery(selectElement).val(selectElement.value).trigger('change.select2');
                    }
                }

                function applyPresetToForm(presetKey) {
                    const preset = presetDefaults[presetKey];

                    if (!preset) {
                        return;
                    }

                    durationInput.value = preset.duration_ms;
                    elevationInput.value = preset.elevation;
                    elevationValue.textContent = preset.elevation;
                    displayModeInput.value = preset.display_mode;
                    renderScaleInput.value = preset.render_scale_percent;
                    gradientsInput.checked = !!preset.gradients;
                    accelerationInput.checked = !!preset.acceleration;
                    syncSelect(displayModeInput);
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

                function resolveBookLayout(rawViewport, settings) {
                    const stageWidth = Math.max(flipbookStageEl.clientWidth - 32, 280);
                    const stageHeight = Math.max(flipbookStageEl.clientHeight - 32, 360);
                    const maxSingleWidth = settings.display === 'double' ? stageWidth / 2 : stageWidth;
                    const layoutScale = Math.max(
                        Math.min(maxSingleWidth / rawViewport.width, stageHeight / rawViewport.height),
                        0.2
                    );
                    const pageWidth = Math.floor(rawViewport.width * layoutScale);
                    const pageHeight = Math.floor(rawViewport.height * layoutScale);
                    const renderScale = Math.max(settings.renderScale, 1);

                    return {
                        pageWidth,
                        pageHeight,
                        bookWidth: settings.display === 'double' ? pageWidth * 2 : pageWidth,
                        bookHeight: pageHeight,
                        canvasScale: layoutScale * renderScale,
                    };
                }

                function scheduleRender(delay = 180) {
                    window.clearTimeout(renderTimer);
                    renderTimer = window.setTimeout(() => {
                        renderAll();
                    }, delay);
                }

                function buildPageShells(pageCount) {
                    flipbookEl.innerHTML = '';
                    for (let i = 1; i <= pageCount; i++) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page bg-white overflow-hidden shadow rounded-3';
                        pageDiv.dataset.pageNumber = String(i);

                        const canvas = document.createElement('canvas');
                        canvas.className = 'w-100 h-100 d-block';
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

                    flipbookInstance = null;
                    setNavigationEnabled(false);
                }

                async function renderAll() {
                    const currentRenderGeneration = ++renderGeneration;
                    const hasTurnJs = !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.turn ===
                        'function');

                    if (!window.pdfjsLib) {
                        setStatus('PDF.js failed to load.');
                        return;
                    }

                    setStatus('Loading PDF…');
                    setNavigationEnabled(false);

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

                    if (currentRenderGeneration !== renderGeneration) {
                        return;
                    }

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

                    const layout = resolveBookLayout(rawViewport, cfg);

                    const pageDivs = flipbookEl.querySelectorAll('.page');

                    for (let i = 1; i <= pageCount; i++) {
                        setStatus(`Rendering page ${i} of ${pageCount}…`);
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({
                            scale: layout.canvasScale
                        });

                        if (currentRenderGeneration !== renderGeneration) {
                            return;
                        }

                        const pageDiv = pageDivs[i - 1];
                        pageDiv.style.width = layout.pageWidth + 'px';
                        pageDiv.style.height = layout.pageHeight + 'px';

                        const canvas = pageDiv.querySelector('canvas');
                        const ctx = canvas.getContext('2d');

                        canvas.width = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);

                        await page.render({
                            canvasContext: ctx,
                            viewport
                        }).promise;
                    }

                    if (!hasTurnJs) {
                        flipbookEl.style.width = layout.pageWidth + 'px';
                        flipbookEl.style.height = layout.pageHeight + 'px';
                        setStatus('Turn.js failed to load. Showing a static preview.');
                        return;
                    }

                    setStatus('Ready');

                    const $flipbook = $('#flipbook');
                    flipbookEl.style.width = layout.bookWidth + 'px';
                    flipbookEl.style.height = layout.bookHeight + 'px';
                    $flipbook.turn({
                        width: layout.bookWidth,
                        height: layout.bookHeight,
                        autoCenter: true,
                        display: cfg.display,
                        duration: cfg.duration,
                        acceleration: cfg.acceleration,
                        gradients: cfg.gradients,
                        elevation: cfg.elevation,
                    });

                    flipbookInstance = $flipbook;
                    setNavigationEnabled(true);
                    prevButton.onclick = () => flipbookInstance.turn('previous');
                    nextButton.onclick = () => flipbookInstance.turn('next');

                    if (!keyboardBound) {
                        window.addEventListener('keydown', (event) => {
                            if (!flipbookInstance) {
                                return;
                            }

                            if (event.key === 'ArrowLeft') {
                                flipbookInstance.turn('previous');
                            }

                            if (event.key === 'ArrowRight') {
                                flipbookInstance.turn('next');
                            }
                        });
                        keyboardBound = true;
                    }
                }

                document.getElementById('btnApply').addEventListener('click', () => {
                    renderAll();
                });

                presetInput.addEventListener('change', (event) => {
                    applyPresetToForm(event.target.value);
                    scheduleRender(0);
                });

                elevationInput.addEventListener('input', () => {
                    elevationValue.textContent = elevationInput.value;
                });

                previewFields.forEach((field) => {
                    const eventName = field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input';
                    field.addEventListener(eventName, () => {
                        if (field !== presetInput) {
                            scheduleRender();
                        }
                    });
                });

                window.addEventListener('resize', () => scheduleRender(120));

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

                    const showCopySuccess = () => {
                        document.getElementById('copySuccess').classList.remove('d-none');
                        setTimeout(function() {
                            document.getElementById('copySuccess').classList.add('d-none');
                        }, 3000);
                    };

                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        navigator.clipboard.writeText(linkInput.value).then(showCopySuccess).catch(function() {
                            document.execCommand('copy');
                            showCopySuccess();
                        });
                        return;
                    }

                    document.execCommand('copy');
                    showCopySuccess();
                });

                if (!hasOldPreset) {
                    applyPresetToForm(presetInput.value || @json($setting->preset));
                } else {
                    elevationValue.textContent = elevationInput.value;
                }

                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
