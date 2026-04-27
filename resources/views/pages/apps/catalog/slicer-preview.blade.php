<x-default-layout>

    @section('title')
        Shoppable Flipbook Preview
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.slicer.preview', $pdf) }}
    @endsection

    <div class="card shadow-sm border-0 mb-6">
        <div class="card-body d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4 py-5">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light" id="btnPrev" disabled>
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button type="button" class="btn btn-sm btn-light" id="btnNext" disabled>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
                <span class="badge badge-light-dark fs-7 fw-bold" id="pageIndicator">Page 1 of 1</span>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light-info" id="btnFullscreen">
                    <i class="bi bi-fullscreen"></i> Fullscreen
                </button>
                <button type="button" class="btn btn-sm btn-light-success" id="btnShare">
                    <i class="bi bi-share"></i> Share
                </button>
                <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left"></i> Editor
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-6 preview-stage-card" id="previewStageCard">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="badge badge-light-info fs-7 fw-bold" id="status">
                    <i class="ki-outline ki-loading fs-6 me-1"></i>
                    Loading…
                </div>
            </div>
            <div class="card-toolbar">
                <a class="btn btn-sm btn-light-primary fw-bold" href="{{ route('catalog.pdfs.download', $pdf) }}">
                    <i class="bi bi-download me-1"></i>
                    Download PDF
                </a>
            </div>
        </div>

        <div class="card-body d-flex flex-column gap-5 pt-0" id="previewStageBody">
            <div id="flipbookStage"
                class="d-flex justify-content-center align-items-center bg-light-primary rounded-4 border border-dashed border-primary p-4 p-xl-6 overflow-auto flex-grow-1">
                <div id="flipbook" class="mx-auto"></div>
            </div>

            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6"
                id="previewInfoAlert">
                <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                <div class="d-flex flex-column gap-1">
                    <div class="fw-bold text-gray-900">Interactive Hotspots</div>
                    <div class="text-muted">Click highlighted areas to open internal pages, external links, product
                        details, images, or videos.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalProductTitle">Product Details</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body pt-5 pb-8 px-10">
                    <div class="d-flex align-items-start gap-5">
                        <div class="symbol symbol-100px symbol-lg-150px flex-shrink-0 d-none"
                            id="modalProductThumbWrapper">
                            <img id="modalProductThumb" src="" alt="" class="w-100 h-100 rounded-3">
                        </div>

                        <div class="flex-grow-1">
                            <div class="fs-3 fw-bold text-gray-900 mb-3" id="modalProductName"></div>
                            <div class="fs-6 text-gray-600 mb-5" id="modalProductDesc"></div>
                            <div class="d-flex align-items-center">
                                <span class="fs-2x fw-bold text-primary" id="modalProductPrice"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <a href="#" target="_blank" class="btn btn-primary px-8 fw-bold" id="modalProductLink">
                        <i class="ki-outline ki-shop fs-3 me-2"></i>
                        View Product
                    </a>
                    <button type="button" class="btn btn-light px-8 fw-bold" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalImageTitle">Image Preview</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body p-10 text-center overflow-auto">
                    <img id="modalImageEl" src="" alt="" class="img-fluid rounded-3 mw-100">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVideo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalVideoTitle">Video Player</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body p-10">
                    <div id="modalVideoWrap" class="ratio ratio-16x9 rounded-3 overflow-hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Share Shoppable Flipbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Share this link to allow others to view the interactive shoppable flipbook:
                    </p>
                    <div class="input-group mt-4">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.slicer.share', $pdf) }}">
                        <button type="button" class="btn btn-primary" id="btnCopyLink">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="{{ route('catalog.pdfs.share-preview.edit', $pdf) }}" class="btn btn-light-info">
                            <i class="bi bi-layout-text-window-reverse me-2"></i> Share Preview Studio
                        </a>
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
                const pages = @json($pages);
                const hotspots = @json($hotspots);
                const previewStageCard = document.getElementById('previewStageCard');
                const previewStageBody = document.getElementById('previewStageBody');
                const flipbookStageEl = document.getElementById('flipbookStage');
                const previewInfoAlertEl = document.getElementById('previewInfoAlert');
                const fullscreenButton = document.getElementById('btnFullscreen');
                const previousButton = document.getElementById('btnPrev');
                const nextButton = document.getElementById('btnNext');
                const shareButton = document.getElementById('btnShare');
                const copyLinkButton = document.getElementById('btnCopyLink');
                const shareModalEl = document.getElementById('shareModal');
                const shareLinkEl = document.getElementById('shareLink');
                const copySuccessEl = document.getElementById('copySuccess');
                const RENDER_SCALE_MULTIPLIER = 1.35;
                const MAX_RENDER_SCALE = 1.6;
                const PAGE_RENDER_TIMEOUT_MS = 12000;

                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                const statusEl = document.getElementById('status');
                const flipbookEl = document.getElementById('flipbook');
                const pageIndicatorEl = document.getElementById('pageIndicator');
                const modalVideoEl = document.getElementById('modalVideo');
                const modalVideoWrapEl = document.getElementById('modalVideoWrap');
                const modalProductThumbWrapperEl = document.getElementById('modalProductThumbWrapper');
                const modalProductThumbEl = document.getElementById('modalProductThumb');
                const modalProductLinkEl = document.getElementById('modalProductLink');
                const modalImageEl = document.getElementById('modalImageEl');

                let $flipbook = null;
                let keyboardBound = false;
                let resizeTimer = null;
                let pdfDocumentPromise = null;
                let pdfDocument = null;
                let renderGeneration = 0;
                const colorResolverEl = document.createElement('span');
                const resolvedColorCache = new Map();

                colorResolverEl.style.display = 'none';
                document.body.appendChild(colorResolverEl);

                const mediaBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/hotspots'));
                const flipSoundUrl = @json(asset('assets/media/sounds/page-flip-new.mp3'));
                const statusTones = ['badge-light-info', 'badge-light-success', 'badge-light-warning',
                    'badge-light-danger'
                ];
                let flipSound = null;
                let lastTurnedPage = 1;

                function createFlipSound() {
                    try {
                        const audio = new Audio(flipSoundUrl);
                        audio.preload = 'auto';
                        audio.volume = 0.35;
                        return audio;
                    } catch (error) {
                        return null;
                    }
                }

                function playFlipSoundIfPageChanged(pageNumber = null) {
                    const current = Number(pageNumber || (hasActiveFlipbook() ? $flipbook.turn('page') : 1) || 1);
                    const shouldPlay = current !== lastTurnedPage;
                    lastTurnedPage = current;

                    if (!shouldPlay || !flipSound) {
                        return;
                    }

                    try {
                        flipSound.currentTime = 0;
                        flipSound.play().catch(() => {});
                    } catch (error) {}
                }

                const hotspotByPageId = {};
                for (const h of hotspots) {
                    const pid = String(h.catalog_pdf_page_id);
                    hotspotByPageId[pid] = hotspotByPageId[pid] || [];
                    hotspotByPageId[pid].push(h);
                }

                function setStatus(text, tone = 'info', icon = 'ki-outline ki-loading') {
                    statusEl.classList.remove(...statusTones);
                    statusEl.classList.add(`badge-light-${tone}`);
                    statusEl.innerHTML = `<i class="${icon} fs-6 me-1"></i>${text}`;
                }

                function setNavigationEnabled(enabled) {
                    previousButton.disabled = !enabled;
                    nextButton.disabled = !enabled;
                }

                function hasTurnSupport() {
                    return !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.turn === 'function');
                }

                function hasActiveFlipbook() {
                    if (!$flipbook || typeof $flipbook.turn !== 'function') {
                        return false;
                    }

                    try {
                        return Number($flipbook.turn('pages')) > 0;
                    } catch (error) {
                        return false;
                    }
                }

                async function getPdfDocument() {
                    if (pdfDocument) {
                        return pdfDocument;
                    }

                    if (!pdfDocumentPromise) {
                        pdfDocumentPromise = window.pdfjsLib.getDocument(pdfUrl).promise.then(function(document) {
                            pdfDocument = document;
                            return document;
                        });
                    }

                    return pdfDocumentPromise;
                }

                function updatePageIndicator(message = null) {
                    if (message) {
                        pageIndicatorEl.textContent = message;
                        return;
                    }

                    if (!hasActiveFlipbook()) {
                        pageIndicatorEl.textContent = pages.length ? `${pages.length} pages` : 'Page 1 of 1';
                        return;
                    }

                    const current = $flipbook.turn('page');
                    const total = $flipbook.turn('pages');
                    pageIndicatorEl.textContent = `Page ${current} of ${total}`;
                }

                function isStageFullscreen() {
                    return document.fullscreenElement === previewStageCard;
                }

                function updateFullscreenButton() {
                    const icon = isStageFullscreen() ? 'bi-fullscreen-exit' : 'bi-fullscreen';
                    const label = isStageFullscreen() ? 'Exit Fullscreen' : 'Fullscreen';
                    fullscreenButton.innerHTML = `<i class="bi ${icon}"></i> ${label}`;
                }

                function syncStageState() {
                    const fullscreen = isStageFullscreen();
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 900;
                    const stageHeight = fullscreen ?
                        Math.max(viewportHeight - 142, 320) :
                        Math.max(Math.min(Math.floor(viewportHeight * 0.72), 920), 520);

                    previewStageCard.classList.toggle('rounded-0', fullscreen);
                    previewStageCard.classList.toggle('shadow-none', fullscreen);
                    previewStageCard.classList.toggle('bg-dark', fullscreen);
                    previewStageBody.classList.toggle('h-100', fullscreen);

                    flipbookStageEl.classList.toggle('bg-light-primary', !fullscreen);
                    flipbookStageEl.classList.toggle('border', !fullscreen);
                    flipbookStageEl.classList.toggle('border-dashed', !fullscreen);
                    flipbookStageEl.classList.toggle('border-primary', !fullscreen);
                    flipbookStageEl.classList.toggle('rounded-4', !fullscreen);
                    flipbookStageEl.classList.toggle('bg-dark', fullscreen);
                    flipbookStageEl.classList.toggle('rounded-0', fullscreen);

                    previewInfoAlertEl.classList.toggle('d-none', fullscreen);

                    previewStageCard.style.width = fullscreen ? '100%' : '';
                    previewStageCard.style.height = fullscreen ? `${viewportHeight}px` : '';
                    previewStageBody.style.height = fullscreen ? '100%' : '';
                    flipbookStageEl.style.height = `${stageHeight}px`;

                    updateFullscreenButton();
                }

                function mediaUrl(id, kind) {
                    return mediaBase + '/' + id + '/media/' + kind;
                }

                function clamp(value, min, max) {
                    return Math.min(Math.max(value, min), max);
                }

                function resolveCssColor(value) {
                    const trimmed = String(value ?? '').trim();
                    if (!trimmed) {
                        return '';
                    }

                    if (resolvedColorCache.has(trimmed)) {
                        return resolvedColorCache.get(trimmed);
                    }

                    if (!window.CSS?.supports?.('color', trimmed)) {
                        resolvedColorCache.set(trimmed, '');
                        return '';
                    }

                    colorResolverEl.style.color = trimmed;
                    const resolved = window.getComputedStyle(colorResolverEl).color || '';
                    resolvedColorCache.set(trimmed, resolved);

                    return resolved;
                }

                function alphaColor(color, alpha) {
                    const resolved = resolveCssColor(color);
                    const match = resolved.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)$/i);

                    if (!match) {
                        return '';
                    }

                    return `rgba(${match[1]}, ${match[2]}, ${match[3]}, ${alpha})`;
                }

                function hotspotVisualTokens(color, isHover = false) {
                    return {
                        surfaceTop: colorWithFallback(color, isHover ? 0.18 : 0.10, isHover ?
                            'rgba(255, 255, 255, 0.18)' : 'rgba(255, 255, 255, 0.10)'),
                        surfaceBottom: colorWithFallback(color, isHover ? 0.12 : 0.08, isHover ?
                            'rgba(226, 232, 240, 0.14)' : 'rgba(226, 232, 240, 0.08)'),
                        borderColor: colorWithFallback(color, 0.42, 'rgba(255, 255, 255, 0.72)'),
                        insetHighlight: isHover ? 'rgba(255, 255, 255, 0.82)' : 'rgba(255, 255, 255, 0.72)',
                        insetShade: colorWithFallback(color, isHover ? 0.24 : 0.18, 'rgba(148, 163, 184, 0.22)'),
                        outerShadow: colorWithFallback(color, isHover ? 0.24 : 0.18, isHover ?
                            'rgba(148, 163, 184, 0.34)' : 'rgba(148, 163, 184, 0.28)'),
                        castShadow: isHover ? 'rgba(15, 23, 42, 0.16)' : 'rgba(15, 23, 42, 0.12)',
                    };
                }

                function colorWithFallback(color, alpha, fallback) {
                    return alphaColor(color, alpha) || fallback;
                }

                function hotspotBoxShadow(tokens, isHover = false) {
                    return [
                        `inset 1px 1px 0 ${tokens.insetHighlight}`,
                        `inset -1px -1px 0 ${tokens.insetShade}`,
                        `0 ${isHover ? 14 : 10}px ${isHover ? 28 : 22}px ${tokens.outerShadow}`,
                        `0 2px 6px ${tokens.castShadow}`,
                    ].join(', ');
                }

                function applyHotspotButtonVisual(button, color, isHover = false) {
                    const tokens = hotspotVisualTokens(color, isHover);
                    button.style.background =
                        `linear-gradient(145deg, ${tokens.surfaceTop}, ${tokens.surfaceBottom})`;
                    button.style.border = 'none';
                    button.style.boxShadow = hotspotBoxShadow(tokens, isHover);
                    button.style.transform = isHover ? 'translateY(-1px) scale(1.01)' : 'none';
                }

                function normalizedBbox(shape, hotspot) {
                    const bbox = shape && shape.bbox ? shape.bbox : {};
                    const x = Number.isFinite(Number(bbox.x)) ? Number(bbox.x) : Number(hotspot.x || 0);
                    const y = Number.isFinite(Number(bbox.y)) ? Number(bbox.y) : Number(hotspot.y || 0);
                    const w = Number.isFinite(Number(bbox.w)) ? Number(bbox.w) : Number(hotspot.w || 0);
                    const h = Number.isFinite(Number(bbox.h)) ? Number(bbox.h) : Number(hotspot.h || 0);

                    return {
                        x: clamp(x, 0, 1),
                        y: clamp(y, 0, 1),
                        w: Math.max(clamp(w, 0, 1), 0.0001),
                        h: Math.max(clamp(h, 0, 1), 0.0001),
                    };
                }

                function shapeClipPath(shape, bbox) {
                    if (!shape || !Array.isArray(shape.points) || shape.points.length < 3) {
                        return '';
                    }

                    return `polygon(${shape.points.map((point) => {
                        const x = bbox.w > 0 ? ((Number(point.x) - bbox.x) / bbox.w) * 100 : 0;
                        const y = bbox.h > 0 ? ((Number(point.y) - bbox.y) / bbox.h) * 100 : 0;
                        return `${clamp(x, 0, 100).toFixed(2)}% ${clamp(y, 0, 100).toFixed(2)}%`;
                    }).join(', ')})`;
                }

                function createHotspotElement(hotspot) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'position-absolute rounded-3';
                    button.title = hotspot.title || hotspot.action_type;
                    button.setAttribute('aria-label', hotspot.title || hotspot.action_type);

                    const shape = hotspot.runtime_shape || hotspot.shape_data?.runtimeShape || hotspot.shape_data
                        ?.runtime_shape || null;
                    const bbox = normalizedBbox(shape, hotspot);

                    button.style.left = `${bbox.x * 100}%`;
                    button.style.top = `${bbox.y * 100}%`;
                    button.style.width = `${bbox.w * 100}%`;
                    button.style.height = `${bbox.h * 100}%`;
                    button.style.padding = '0';
                    button.style.margin = '0';
                    button.style.cursor = 'pointer';
                    button.style.transition =
                        'transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease';
                    applyHotspotButtonVisual(button, hotspot.color, false);

                    if (shape && shape.type !== 'rectangle') {
                        const clipPath = shapeClipPath(shape, bbox);
                        if (clipPath) {
                            button.style.clipPath = clipPath;
                            button.style.borderRadius = '0';
                        }
                    }

                    button.addEventListener('mouseenter', () => {
                        applyHotspotButtonVisual(button, hotspot.color, true);
                    });

                    button.addEventListener('mouseleave', () => {
                        applyHotspotButtonVisual(button, hotspot.color, false);
                    });

                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        handleAction(hotspot);
                    });

                    return button;
                }

                function buildPageShells(layout) {
                    flipbookEl.innerHTML = '';
                    flipbookEl.className = 'mx-auto';

                    for (const p of pages) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page bg-white overflow-hidden rounded-4 shadow-sm';
                        pageDiv.dataset.pageId = String(p.id);
                        pageDiv.dataset.pageNumber = String(p.page_number);
                        pageDiv.style.width = `${layout.pageWidth}px`;
                        pageDiv.style.height = `${layout.pageHeight}px`;

                        const inner = document.createElement('div');
                        inner.className = 'position-relative w-100 h-100 overflow-hidden rounded-4';

                        if (p.image_path) {
                            const img = document.createElement('img');
                            img.src = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages')) + '/' + p.id + '/image';
                            img.alt = p.title || ('Page ' + p.page_number);
                            img.className = 'w-100 h-100 d-block';
                            img.loading = 'eager';
                            img.decoding = 'async';
                            inner.appendChild(img);
                        } else {
                            const canvas = document.createElement('canvas');
                            canvas.className = 'w-100 h-100 d-block';
                            canvas.width = 10;
                            canvas.height = 10;
                            inner.appendChild(canvas);
                        }

                        const hs = hotspotByPageId[String(p.id)] || [];
                        for (const h of hs) {
                            inner.appendChild(createHotspotElement(h));
                        }

                        pageDiv.appendChild(inner);
                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function computeLayout(rawViewport) {
                    const stageStyles = window.getComputedStyle(flipbookStageEl);
                    const horizontalPadding = (parseFloat(stageStyles.paddingLeft) || 0) + (parseFloat(stageStyles
                        .paddingRight) || 0);
                    const verticalPadding = (parseFloat(stageStyles.paddingTop) || 0) + (parseFloat(stageStyles
                        .paddingBottom) || 0);
                    const stageWidth = Math.max(flipbookStageEl.clientWidth - horizontalPadding - 8, 280);
                    const stageHeight = Math.max(flipbookStageEl.clientHeight - verticalPadding - 8, 320);
                    const display = stageWidth < 900 || window.innerWidth < 992 ? 'single' : 'double';
                    const singlePageWidth = display === 'double' ? stageWidth / 2 : stageWidth;
                    const displayScale = Math.max(Math.min(singlePageWidth / rawViewport.width, stageHeight /
                        rawViewport.height), 0.25);
                    const pageWidth = Math.max(Math.floor(rawViewport.width * displayScale), 180);
                    const pageHeight = Math.max(Math.floor(rawViewport.height * displayScale), 240);

                    return {
                        display,
                        pageWidth,
                        pageHeight,
                        bookWidth: display === 'double' ? pageWidth * 2 : pageWidth,
                        bookHeight: pageHeight,
                        canvasScale: Math.min(displayScale * RENDER_SCALE_MULTIPLIER, MAX_RENDER_SCALE),
                    };
                }

                function pageIndexForPageNumber(pageNumber) {
                    const idx = pages.findIndex(p => Number(p.page_number) === Number(pageNumber));
                    return idx >= 0 ? idx + 1 : null;
                }

                function waitForImages(container, timeoutMs = 8000) {
                    const imgs = Array.from(container.querySelectorAll('img'));
                    if (imgs.length === 0) return Promise.resolve();

                    return Promise.all(imgs.map(img => {
                        if (img.complete) return Promise.resolve();

                        return new Promise((resolve) => {
                            const timeoutId = window.setTimeout(() => {
                                resolve();
                            }, timeoutMs);
                            const finish = () => {
                                window.clearTimeout(timeoutId);
                                resolve();
                            };

                            img.addEventListener('load', finish, {
                                once: true
                            });
                            img.addEventListener('error', finish, {
                                once: true
                            });
                        });
                    }));
                }

                function showModal(modalId) {
                    const el = document.getElementById(modalId);
                    if (!el || !window.bootstrap) return;
                    const modal = new window.bootstrap.Modal(el);
                    modal.show();
                }

                function drawPageFallback(canvas, pageNumber) {
                    const context = canvas.getContext('2d');
                    const width = canvas.width || 900;
                    const height = canvas.height || 1200;

                    context.clearRect(0, 0, width, height);
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, width, height);
                    context.strokeStyle = '#d0d5dd';
                    context.lineWidth = Math.max(2, Math.floor(width / 320));
                    context.strokeRect(0, 0, width, height);
                    context.fillStyle = '#3f4254';
                    context.textAlign = 'center';
                    context.font = `${Math.max(24, Math.floor(width / 18))}px sans-serif`;
                    context.fillText(`Page ${pageNumber}`, width / 2, height / 2 - 14);
                    context.fillStyle = '#7e8299';
                    context.font = `${Math.max(14, Math.floor(width / 34))}px sans-serif`;
                    context.fillText('Preview rendering fallback', width / 2, height / 2 + 28);
                }

                async function renderPdfPageToCanvas(pdfPage, canvas, scale) {
                    const viewport = pdfPage.getViewport({
                        scale
                    });
                    const context = canvas.getContext('2d');
                    const renderTask = pdfPage.render({
                        canvasContext: context,
                        viewport
                    });

                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);

                    let timeoutId = null;

                    try {
                        await Promise.race([
                            renderTask.promise,
                            new Promise((resolve, reject) => {
                                timeoutId = window.setTimeout(() => {
                                    try {
                                        renderTask.cancel();
                                    } catch (error) {}

                                    reject(new Error('Page render timeout'));
                                }, PAGE_RENDER_TIMEOUT_MS);
                            })
                        ]);
                    } finally {
                        if (timeoutId) {
                            window.clearTimeout(timeoutId);
                        }
                    }
                }

                async function renderPageWithRecovery(pdfPage, pageNumber, canvas, layout) {
                    const retryScale = Math.max(Math.min(layout.canvasScale * 0.72, 1.05), 0.7);

                    try {
                        await renderPdfPageToCanvas(pdfPage, canvas, layout.canvasScale);
                        return true;
                    } catch (error) {
                        console.warn(`Primary render failed for page ${pageNumber}. Retrying at lower scale.`, error);
                    }

                    try {
                        await renderPdfPageToCanvas(pdfPage, canvas, retryScale);
                        return true;
                    } catch (error) {
                        console.error(`Fallback render failed for page ${pageNumber}.`, error);
                        drawPageFallback(canvas, pageNumber);
                        return false;
                    }
                }

                function destroyTurnIfExists() {
                    try {
                        if (hasActiveFlipbook()) {
                            $('#flipbook').turn('destroy');
                        }
                    } catch (error) {}

                    $flipbook = null;
                    setNavigationEnabled(false);
                }

                function configureStaticPreview() {
                    flipbookEl.className = 'd-flex flex-column align-items-center gap-6 w-100';
                    flipbookEl.style.width = '100%';
                    flipbookEl.style.height = 'auto';
                    flipbookEl.querySelectorAll('.page').forEach((pageEl) => {
                        pageEl.classList.add('mx-auto');
                    });
                    updatePageIndicator('Static preview');
                    setStatus('Turn.js unavailable. Showing static preview.', 'warning',
                        'ki-outline ki-information-5');
                }

                function stopModalVideoPlayback() {
                    if (!modalVideoWrapEl) return;

                    modalVideoWrapEl.querySelectorAll('video').forEach((video) => {
                        video.pause();
                        video.removeAttribute('src');
                        video.load();
                    });

                    modalVideoWrapEl.querySelectorAll('iframe').forEach((iframe) => {
                        iframe.setAttribute('src', 'about:blank');
                    });

                    modalVideoWrapEl.innerHTML = '';
                }

                modalVideoEl?.addEventListener('hide.bs.modal', stopModalVideoPlayback);

                function handleAction(h) {
                    if (h.action_type === 'internal_page') {
                        if (hasActiveFlipbook()) {
                            const idx = pageIndexForPageNumber(h.internal_page_number);
                            if (idx) {
                                $flipbook.turn('page', idx);
                            }
                        } else {
                            const targetPage = flipbookEl.querySelector(
                                `.page[data-page-number="${h.internal_page_number}"]`
                            );
                            targetPage?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }

                        return;
                    }

                    if (h.action_type === 'external_link') {
                        if (h.link) {
                            window.open(h.link, '_blank', 'noopener,noreferrer');
                        }

                        return;
                    }

                    if (h.action_type === 'popup_window') {
                        document.getElementById('modalProductTitle').textContent = h.title || 'Product Details';
                        document.getElementById('modalProductName').textContent = h.title || '';
                        document.getElementById('modalProductDesc').textContent = h.description || '';
                        document.getElementById('modalProductPrice').textContent = h.price ? h.price : '';

                        if (h.thumbnail_path) {
                            modalProductThumbEl.src = h.thumbnail_url || mediaUrl(h.id, 'thumbnail');
                            modalProductThumbWrapperEl.classList.remove('d-none');
                        } else {
                            modalProductThumbEl.removeAttribute('src');
                            modalProductThumbWrapperEl.classList.add('d-none');
                        }

                        modalProductLinkEl.href = h.link || '#';
                        modalProductLinkEl.classList.toggle('d-none', !h.link);

                        showModal('modalProduct');
                        return;
                    }

                    if (h.action_type === 'popup_image') {
                        document.getElementById('modalImageTitle').textContent = h.title || 'Image Preview';
                        modalImageEl.src = h.popup_image_url || mediaUrl(h.id, 'popup_image');
                        showModal('modalImage');
                        return;
                    }

                    if (h.action_type === 'popup_video') {
                        document.getElementById('modalVideoTitle').textContent = h.title || 'Video Player';
                        const wrap = modalVideoWrapEl;
                        stopModalVideoPlayback();

                        if (h.popup_video_url) {
                            const iframe = document.createElement('iframe');
                            iframe.src = h.popup_video_url;
                            iframe.style.width = '100%';
                            iframe.style.height = '100%';
                            iframe.setAttribute('allowfullscreen', 'true');
                            iframe.setAttribute('frameborder', '0');
                            wrap.appendChild(iframe);
                        } else {
                            const video = document.createElement('video');
                            video.controls = true;
                            video.style.width = '100%';
                            video.src = h.popup_video_file_url || mediaUrl(h.id, 'popup_video');
                            wrap.appendChild(video);
                        }

                        showModal('modalVideo');
                        return;
                    }
                }

                async function renderMissingCanvases(pdf, layout, currentRenderGeneration) {
                    const pageDivs = flipbookEl.querySelectorAll('.page');
                    for (let i = 0; i < pages.length; i++) {
                        if (currentRenderGeneration !== renderGeneration) {
                            return;
                        }

                        const p = pages[i];
                        if (p.image_path) {
                            continue;
                        }

                        setStatus(`Rendering page ${i + 1} of ${pages.length}…`);
                        const page = await pdf.getPage(p.page_number);
                        const viewport = page.getViewport({
                            scale: layout.canvasScale
                        });

                        const pageDiv = pageDivs[i];
                        pageDiv.style.width = layout.pageWidth + 'px';
                        pageDiv.style.height = layout.pageHeight + 'px';

                        const canvas = pageDiv.querySelector('canvas');

                        try {
                            await renderPageWithRecovery(page, p.page_number, canvas, layout);
                        } catch (error) {
                            console.error(`Unexpected render error for page ${p.page_number}.`, error);
                            canvas.width = Math.max(1, Math.floor(viewport.width));
                            canvas.height = Math.max(1, Math.floor(viewport.height));
                            drawPageFallback(canvas, p.page_number);
                        }
                    }
                }

                function scheduleRender() {
                    if (resizeTimer) {
                        window.clearTimeout(resizeTimer);
                    }

                    resizeTimer = window.setTimeout(() => {
                        renderAll();
                    }, 180);
                }

                async function renderAll() {
                    const currentRenderGeneration = ++renderGeneration;

                    if (!pages || pages.length === 0) {
                        setStatus('No pages initialized yet. Open the editor and initialize pages.', 'warning',
                            'ki-outline ki-information-5');
                        updatePageIndicator('No pages');
                        return;
                    }

                    if (!window.pdfjsLib) {
                        setStatus('PDF.js failed to load.', 'danger', 'ki-outline ki-shield-cross');
                        return;
                    }

                    setStatus('Loading PDF…');
                    let pdf;
                    try {
                        pdf = await getPdfDocument();
                    } catch (error) {
                        console.error(error);
                        setStatus('Failed to load PDF.', 'danger', 'ki-outline ki-shield-cross');
                        return;
                    }

                    const currentPage = hasActiveFlipbook() ? $flipbook.turn('page') : 1;
                    lastTurnedPage = Number(currentPage || 1);
                    if (!flipSound) {
                        flipSound = createFlipSound();
                    }
                    destroyTurnIfExists();
                    syncStageState();

                    const firstPage = await pdf.getPage(pages[0].page_number);
                    if (currentRenderGeneration !== renderGeneration) {
                        return;
                    }

                    const rawViewport = firstPage.getViewport({
                        scale: 1
                    });
                    const layout = computeLayout(rawViewport);

                    setStatus('Building pages…');
                    buildPageShells(layout);

                    await renderMissingCanvases(pdf, layout, currentRenderGeneration);
                    if (currentRenderGeneration !== renderGeneration) {
                        return;
                    }

                    setStatus('Loading images…');
                    await waitForImages(flipbookEl);
                    if (currentRenderGeneration !== renderGeneration) {
                        return;
                    }

                    if (!hasTurnSupport()) {
                        configureStaticPreview();
                        return;
                    }

                    try {
                        $flipbook = $('#flipbook');
                        flipbookEl.style.width = layout.bookWidth + 'px';
                        flipbookEl.style.height = layout.bookHeight + 'px';
                        $flipbook.turn({
                            width: layout.bookWidth,
                            height: layout.bookHeight,
                            autoCenter: true,
                            display: layout.display,
                            acceleration: true,
                            gradients: true,
                            duration: 850,
                        });

                        $flipbook.bind('turned', function() {
                            updatePageIndicator();
                            playFlipSoundIfPageChanged();
                        });

                        if (currentPage > 1) {
                            $flipbook.turn('page', Math.min(currentPage, pages.length));
                        }

                        updatePageIndicator();
                        setNavigationEnabled(true);
                        setStatus('Ready', 'success', 'ki-outline ki-check-circle');
                    } catch (error) {
                        console.error(error);
                        configureStaticPreview();
                    }
                }

                async function toggleFullscreen() {
                    try {
                        if (!isStageFullscreen()) {
                            await previewStageCard.requestFullscreen();
                        } else {
                            await document.exitFullscreen();
                        }
                    } catch (error) {
                        console.error(error);
                    }
                }

                async function copyShareLink() {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        await navigator.clipboard.writeText(shareLinkEl.value);
                        return;
                    }

                    shareLinkEl.focus();
                    shareLinkEl.select();
                    document.execCommand('copy');
                }

                shareButton.addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(shareModalEl);
                    shareModal.show();
                });

                copyLinkButton.addEventListener('click', function() {
                    copyShareLink().then(function() {
                        copySuccessEl.classList.remove('d-none');
                        setTimeout(function() {
                            copySuccessEl.classList.add('d-none');
                        }, 3000);
                    }).catch(function(err) {
                        alert('Failed to copy link: ' + err);
                    });
                });

                previousButton.addEventListener('click', function() {
                    if (hasActiveFlipbook()) {
                        $flipbook.turn('previous');
                    }
                });

                nextButton.addEventListener('click', function() {
                    if (hasActiveFlipbook()) {
                        $flipbook.turn('next');
                    }
                });

                fullscreenButton.addEventListener('click', toggleFullscreen);

                if (!keyboardBound) {
                    window.addEventListener('keydown', function(event) {
                        const tag = (event.target && event.target.tagName) ? event.target.tagName.toLowerCase() :
                            '';
                        if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                            return;
                        }

                        if (event.key === 'ArrowLeft' && hasActiveFlipbook()) {
                            $flipbook.turn('previous');
                        }

                        if (event.key === 'ArrowRight' && hasActiveFlipbook()) {
                            $flipbook.turn('next');
                        }

                        if (event.key === 'Escape' && isStageFullscreen()) {
                            document.exitFullscreen().catch(() => {});
                        }
                    });
                    keyboardBound = true;
                }

                window.addEventListener('resize', function() {
                    syncStageState();
                    scheduleRender();
                });

                document.addEventListener('fullscreenchange', function() {
                    syncStageState();
                    scheduleRender();
                });

                syncStageState();
                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
