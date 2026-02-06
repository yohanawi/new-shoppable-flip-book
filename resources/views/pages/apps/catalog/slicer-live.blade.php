<x-default-layout>

    @section('title')
        Live Shoppable Flipbook
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.slicer.live', $pdf) }}
    @endsection

    <style>
        .live-wrap {
            min-height: calc(100vh - 140px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .live-wrap::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        #flipbook {
            margin: 0 auto;
            user-select: none;
            position: relative;
            z-index: 1;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            cursor: grab;
        }

        #flipbook.is-grabbing .page {
            cursor: grabbing;
        }

        .page-inner {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .page-inner img,
        .page-inner canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .hotspot {
            position: absolute;
            border: 2px solid rgba(var(--bs-primary-rgb), 0.85);
            background: rgba(var(--bs-primary-rgb), 0.10);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .hotspot:hover {
            background: rgba(var(--bs-primary-rgb), 0.22);
            border-color: rgba(var(--bs-primary-rgb), 1);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }

        .toolbar-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .page-indicator {
            font-weight: 700;
            color: var(--bs-gray-900);
            font-size: 1.1rem;
        }

        .btn-toolbar {
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-toolbar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .zoom-badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 8px;
            background: rgba(var(--bs-info-rgb), 0.1);
            color: var(--bs-info);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .toolbar-glass {
                border-radius: 12px;
                padding: 12px !important;
            }
        }
    </style>

    <!--begin::Live Flipbook Card-->
    <div class="card shadow-lg live-wrap border-0">
        <div class="card-body p-6 p-lg-10 position-relative">
            <!--begin::Toolbar-->
            <div class="toolbar-glass p-4 mb-8">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <!--begin::Navigation Controls-->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary btn-toolbar" id="btnPrev"
                            title="Previous Page">
                            <i class="ki-outline ki-left fs-2"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon btn-light-primary btn-toolbar" id="btnNext"
                            title="Next Page">
                            <i class="ki-outline ki-right fs-2"></i>
                        </button>
                        <div class="separator separator-vertical mx-2 h-30px d-none d-sm-block"></div>
                        <div class="page-indicator d-none d-sm-flex align-items-center" id="pageIndicator">
                            <i class="ki-outline ki-book fs-3 text-primary me-2"></i>
                            Page 1 / {{ max(1, $pages->count()) }}
                        </div>
                    </div>
                    <!--end::Navigation Controls-->

                    <!--begin::Status Badge-->
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-success fs-7 fw-bold" id="status">
                            <i class="ki-outline ki-check-circle fs-6 me-1"></i>
                            Initializing...
                        </span>
                    </div>
                    <!--end::Status Badge-->

                    <!--begin::Action Controls-->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-light-info btn-toolbar" id="btnZoomOut"
                                title="Zoom Out">
                                <i class="ki-outline ki-minus fs-3"></i>
                            </button>
                            <button type="button" class="btn btn-light-info btn-toolbar" id="btnZoomIn"
                                title="Zoom In">
                                <i class="ki-outline ki-plus fs-3"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-warning btn-toolbar" id="btnFullscreen">
                            <i class="ki-outline ki-screen fs-4 me-1"></i>
                            <span class="d-none d-sm-inline">Fullscreen</span>
                        </button>
                        <a class="btn btn-sm btn-light btn-toolbar" href="{{ route('catalog.pdfs.show', $pdf) }}">
                            <i class="ki-outline ki-document fs-4 me-1"></i>
                            <span class="d-none d-sm-inline">Details</span>
                        </a>
                    </div>
                    <!--end::Action Controls-->
                </div>
            </div>
            <!--end::Toolbar-->

            <!--begin::Flipbook Container-->
            <div class="d-flex justify-content-center align-items-center py-10">
                <div id="flipbook"></div>
            </div>
            <!--end::Flipbook Container-->

            <!--begin::Help Notice-->
            <div class="alert alert-dismissible bg-white border-0 d-flex align-items-center p-5 mt-8 shadow-sm"
                style="border-radius: 12px;">
                <i class="ki-outline ki-information-5 fs-2tx text-info me-4"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1 fw-bold">How to Navigate</h5>
                    <span class="text-gray-700">
                        <strong>Desktop:</strong> Drag page corners to flip • Use arrow keys or buttons • Scroll to zoom
                        <br class="d-none d-md-block">
                        <strong>Mobile:</strong> Swipe to turn pages • Tap hotspots to interact
                    </span>
                </div>
                <button type="button"
                    class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon btn-sm ms-sm-auto"
                    data-bs-dismiss="alert">
                    <i class="ki-outline ki-cross fs-2 text-gray-500"></i>
                </button>
            </div>
            <!--end::Help Notice-->
        </div>
    </div>
    <!--end::Live Flipbook Card-->

    <!--begin::Product Modal-->
    <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content shadow-lg" style="border-radius: 16px;">
                <!--begin::Modal header-->
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold">Product Details</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body pt-5 pb-8 px-10">
                    <div class="d-flex align-items-start gap-5">
                        <!--begin::Image-->
                        <div class="symbol symbol-100px symbol-lg-150px flex-shrink-0" id="modalProductThumbWrapper"
                            style="display:none;">
                            <img id="modalProductThumb" src="" alt="" class="symbol-label"
                                style="border-radius: 12px;">
                        </div>
                        <!--end::Image-->

                        <!--begin::Info-->
                        <div class="flex-grow-1">
                            <div class="fs-3 fw-bold text-gray-900 mb-3" id="modalProductName"></div>
                            <div class="fs-6 text-gray-600 mb-5" id="modalProductDesc"></div>
                            <div class="d-flex align-items-center">
                                <span class="fs-2x fw-bold text-primary" id="modalProductPrice"></span>
                            </div>
                        </div>
                        <!--end::Info-->
                    </div>
                </div>
                <!--end::Modal body-->

                <!--begin::Modal footer-->
                <div class="modal-footer border-0 pt-0 justify-content-center pb-8">
                    <a href="#" target="_blank" class="btn btn-primary btn-lg px-10 fw-bold" id="modalProductLink"
                        style="border-radius: 10px;">
                        <i class="ki-outline ki-shop fs-3 me-2"></i>
                        View Product
                    </a>
                    <button type="button" class="btn btn-light btn-lg px-10 fw-bold" data-bs-dismiss="modal"
                        style="border-radius: 10px;">
                        Close
                    </button>
                </div>
                <!--end::Modal footer-->
            </div>
        </div>
    </div>
    <!--end::Product Modal-->

    <!--begin::Image Modal-->
    <div class="modal fade" id="modalImage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg" style="border-radius: 16px;">
                <!--begin::Modal header-->
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalImageTitle">Image Preview</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body p-10">
                    <div class="text-center">
                        <img id="modalImageEl" src="" alt="" class="mw-100 shadow-sm"
                            style="max-height: 70vh; border-radius: 12px;">
                    </div>
                </div>
                <!--end::Modal body-->
            </div>
        </div>
    </div>
    <!--end::Image Modal-->

    <!--begin::Video Modal-->
    <div class="modal fade" id="modalVideo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg" style="border-radius: 16px;">
                <!--begin::Modal header-->
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalVideoTitle">Video Player</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body p-10">
                    <div id="modalVideoWrap" class="ratio ratio-16x9 overflow-hidden shadow-sm"
                        style="border-radius: 12px;"></div>
                </div>
                <!--end::Modal body-->
            </div>
        </div>
    </div>
    <!--end::Video Modal-->

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                const pages = @json($pages);
                const hotspots = @json($hotspots);
                const trackUrl = @json(route('catalog.pdfs.slicer.track', $pdf));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const RENDER_SCALE_MULTIPLIER = 1.5;

                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                const statusEl = document.getElementById('status');
                const pageIndicatorEl = document.getElementById('pageIndicator');
                const flipbookEl = document.getElementById('flipbook');
                const fullscreenBtn = document.getElementById('btnFullscreen');
                const zoomOutBtn = document.getElementById('btnZoomOut');
                const zoomInBtn = document.getElementById('btnZoomIn');

                const mediaBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/hotspots'));

                function toast(type, text) {
                    if (window.toastr && typeof window.toastr[type] === 'function') {
                        window.toastr[type](text);
                    }
                }

                function track(eventType, payload) {
                    try {
                        fetch(trackUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(Object.assign({
                                event_type: eventType,
                            }, payload || {})),
                            keepalive: true,
                        }).catch(() => {});
                    } catch (e) {}
                }

                const hotspotByPageId = {};
                for (const h of hotspots) {
                    const pid = String(h.catalog_pdf_page_id);
                    hotspotByPageId[pid] = hotspotByPageId[pid] || [];
                    hotspotByPageId[pid].push(h);
                }

                function setStatus(text, type = 'info') {
                    const iconMap = {
                        'info': 'loading',
                        'success': 'check-circle',
                        'warning': 'information-5',
                        'error': 'cross-circle'
                    };
                    const colorMap = {
                        'info': 'badge-light-info',
                        'success': 'badge-light-success',
                        'warning': 'badge-light-warning',
                        'error': 'badge-light-danger'
                    };

                    statusEl.className = `badge ${colorMap[type]} fs-7 fw-bold`;
                    statusEl.innerHTML = `<i class="ki-outline ki-${iconMap[type]} fs-6 me-1"></i>${text}`;
                }

                function setPageIndicator(current, total) {
                    if (!pageIndicatorEl) return;
                    pageIndicatorEl.innerHTML =
                        `<i class="ki-outline ki-book fs-3 text-primary me-2"></i>Page ${current} / ${total}`;
                }

                function mediaUrl(id, kind) {
                    return mediaBase + '/' + id + '/media/' + kind;
                }

                function buildPageShells() {
                    flipbookEl.innerHTML = '';
                    for (const p of pages) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page';
                        pageDiv.dataset.pageId = String(p.id);
                        pageDiv.dataset.pageNumber = String(p.page_number);

                        const inner = document.createElement('div');
                        inner.className = 'page-inner';

                        if (p.image_path) {
                            const img = document.createElement('img');
                            img.src = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages')) + '/' + p.id + '/image';
                            img.alt = p.title || ('Page ' + p.page_number);
                            inner.appendChild(img);
                        } else {
                            const canvas = document.createElement('canvas');
                            canvas.width = 10;
                            canvas.height = 10;
                            inner.appendChild(canvas);
                        }

                        const hs = hotspotByPageId[String(p.id)] || [];
                        for (const h of hs) {
                            const div = document.createElement('div');
                            div.className = 'hotspot';
                            div.style.left = (h.x * 100) + '%';
                            div.style.top = (h.y * 100) + '%';
                            div.style.width = (h.w * 100) + '%';
                            div.style.height = (h.h * 100) + '%';
                            if (h.color) {
                                div.style.borderColor = h.color;
                                div.style.backgroundColor = 'rgba(0,0,0,0)';
                            }
                            div.title = h.title || h.action_type;
                            div.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                track('hotspot_click', {
                                    page_number: Number(p.page_number),
                                    hotspot_id: h.id,
                                });
                                handleAction(h);
                            });
                            inner.appendChild(div);
                        }

                        pageDiv.appendChild(inner);
                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function computeTurnSize(pageViewport) {
                    const containerWidth = Math.min(1300,
                        (document.fullscreenElement ? window.innerWidth : (document.getElementById(
                                'kt_app_content_container')
                            ?.clientWidth || window.innerWidth))
                    );

                    const targetSingleWidth = Math.min(620, Math.max(360, containerWidth * 0.48));
                    const scale = targetSingleWidth / pageViewport.width;
                    const w = Math.floor(pageViewport.width * scale);
                    const h = Math.floor(pageViewport.height * scale);

                    const isMobile = window.innerWidth < 992;
                    const display = isMobile ? 'single' : 'double';
                    return {
                        w,
                        h,
                        display,
                        scale
                    };
                }

                function pageIndexForPageNumber(pageNumber) {
                    const idx = pages.findIndex(p => Number(p.page_number) === Number(pageNumber));
                    return idx >= 0 ? idx + 1 : null;
                }

                function waitForImages(container) {
                    const imgs = Array.from(container.querySelectorAll('img'));
                    if (imgs.length === 0) return Promise.resolve();

                    return Promise.all(imgs.map(img => {
                        if (img.complete) return Promise.resolve();
                        return new Promise((resolve) => {
                            img.addEventListener('load', resolve, {
                                once: true
                            });
                            img.addEventListener('error', resolve, {
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

                function handleAction(h) {
                    const $flip = $('#flipbook');

                    if (h.action_type === 'internal_page') {
                        const idx = pageIndexForPageNumber(h.internal_page_number);
                        if (idx) $flip.turn('page', idx);
                        return;
                    }

                    if (h.action_type === 'external_link') {
                        if (h.link) window.open(h.link, '_blank');
                        return;
                    }

                    if (h.action_type === 'popup_window') {
                        document.getElementById('modalProductTitle').textContent = h.title || 'Product Details';
                        document.getElementById('modalProductName').textContent = h.title || '';
                        document.getElementById('modalProductDesc').textContent = h.description || '';
                        document.getElementById('modalProductPrice').textContent = h.price ? h.price : '';

                        const thumbWrapper = document.getElementById('modalProductThumbWrapper');
                        const thumb = document.getElementById('modalProductThumb');
                        if (h.thumbnail_path) {
                            thumb.src = mediaUrl(h.id, 'thumbnail');
                            thumbWrapper.style.display = '';
                        } else {
                            thumbWrapper.style.display = 'none';
                        }

                        const link = document.getElementById('modalProductLink');
                        link.href = h.link || '#';
                        link.style.display = h.link ? '' : 'none';

                        showModal('modalProduct');
                        return;
                    }

                    if (h.action_type === 'popup_image') {
                        document.getElementById('modalImageTitle').textContent = h.title || 'Image Preview';
                        document.getElementById('modalImageEl').src = mediaUrl(h.id, 'popup_image');
                        showModal('modalImage');
                        return;
                    }

                    if (h.action_type === 'popup_video') {
                        document.getElementById('modalVideoTitle').textContent = h.title || 'Video Player';
                        const wrap = document.getElementById('modalVideoWrap');
                        wrap.innerHTML = '';

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
                            video.src = mediaUrl(h.id, 'popup_video');
                            wrap.appendChild(video);
                        }

                        showModal('modalVideo');
                        return;
                    }
                }

                async function renderMissingCanvases(pdf, sizing) {
                    const pageDivs = flipbookEl.querySelectorAll('.page');
                    for (let i = 0; i < pages.length; i++) {
                        const p = pages[i];
                        if (p.image_path) continue;

                        setStatus(`Rendering ${i + 1}/${pages.length}`, 'info');
                        const page = await pdf.getPage(p.page_number);
                        const viewport = page.getViewport({
                            scale: sizing.scale * RENDER_SCALE_MULTIPLIER
                        });

                        const pageDiv = pageDivs[i];
                        pageDiv.style.width = sizing.w + 'px';
                        pageDiv.style.height = sizing.h + 'px';

                        const canvas = pageDiv.querySelector('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);
                        await page.render({
                            canvasContext: ctx,
                            viewport
                        }).promise;
                    }
                }

                async function render() {
                    if (!pages || pages.length === 0) {
                        setStatus('No pages available', 'warning');
                        return;
                    }

                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        setStatus('Turn.js failed to load', 'error');
                        toast('error', 'Turn.js failed to load. Flipbook cannot start.');
                        return;
                    }

                    flipbookEl.addEventListener('wheel', (e) => {
                        e.preventDefault();
                    }, {
                        passive: false
                    });

                    flipbookEl.addEventListener('mousedown', () => flipbookEl.classList.add('is-grabbing'));
                    window.addEventListener('mouseup', () => flipbookEl.classList.remove('is-grabbing'));

                    setStatus('Building pages', 'info');
                    buildPageShells();

                    setStatus('Loading PDF', 'info');
                    let pdf;
                    try {
                        pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    } catch (e) {
                        console.error(e);
                        setStatus('PDF load failed', 'error');
                        return;
                    }

                    const firstPage = await pdf.getPage(pages[0].page_number);
                    const rawViewport = firstPage.getViewport({
                        scale: 1
                    });
                    const sizing = computeTurnSize(rawViewport);

                    const totalPages = pages.length;
                    setPageIndicator(1, totalPages);

                    const pageDivs = flipbookEl.querySelectorAll('.page');
                    for (const el of pageDivs) {
                        el.style.width = sizing.w + 'px';
                        el.style.height = sizing.h + 'px';
                    }

                    await renderMissingCanvases(pdf, sizing);

                    setStatus('Loading images', 'info');
                    await waitForImages(flipbookEl);

                    setStatus('Ready to flip', 'success');

                    function makeClickWavDataUri() {
                        const sampleRate = 8000;
                        const durationMs = 40;
                        const sampleCount = Math.floor(sampleRate * (durationMs / 1000));
                        const numChannels = 1;
                        const bitsPerSample = 8;
                        const blockAlign = numChannels * (bitsPerSample / 8);
                        const byteRate = sampleRate * blockAlign;
                        const dataSize = sampleCount * blockAlign;

                        const buffer = new ArrayBuffer(44 + dataSize);
                        const view = new DataView(buffer);

                        function writeStr(offset, str) {
                            for (let i = 0; i < str.length; i++) view.setUint8(offset + i, str.charCodeAt(i));
                        }

                        writeStr(0, 'RIFF');
                        view.setUint32(4, 36 + dataSize, true);
                        writeStr(8, 'WAVE');
                        writeStr(12, 'fmt ');
                        view.setUint32(16, 16, true);
                        view.setUint16(20, 1, true);
                        view.setUint16(22, numChannels, true);
                        view.setUint32(24, sampleRate, true);
                        view.setUint32(28, byteRate, true);
                        view.setUint16(32, blockAlign, true);
                        view.setUint16(34, bitsPerSample, true);
                        writeStr(36, 'data');
                        view.setUint32(40, dataSize, true);

                        for (let i = 0; i < sampleCount; i++) {
                            const t = i / sampleRate;
                            const freq = 600 - 400 * (t / (durationMs / 1000));
                            const s = Math.sin(2 * Math.PI * freq * t) * Math.exp(-t * 6);
                            const v = Math.max(0, Math.min(255, Math.round(128 + s * 100)));
                            view.setUint8(44 + i, v);
                        }

                        const bytes = new Uint8Array(buffer);
                        let bin = '';
                        for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
                        return 'data:audio/wav;base64,' + btoa(bin);
                    }

                    let flipSound = null;
                    try {
                        flipSound = new Audio(makeClickWavDataUri());
                        flipSound.volume = 0.3;
                    } catch (e) {
                        flipSound = null;
                    }

                    const $flipbook = $('#flipbook');
                    const baseBookWidth = (sizing.display === 'double' ? sizing.w * 2 : sizing.w);
                    const baseBookHeight = sizing.h;

                    let zoom = 1.0;
                    const zoomMin = 0.8;
                    const zoomMax = 1.8;
                    const zoomStep = 0.1;

                    function applyZoom(newZoom) {
                        zoom = Math.max(zoomMin, Math.min(zoomMax, Math.round(newZoom * 10) / 10));
                        const w = Math.floor(baseBookWidth * zoom);
                        const h = Math.floor(baseBookHeight * zoom);
                        flipbookEl.style.width = w + 'px';
                        flipbookEl.style.height = h + 'px';
                        try {
                            $flipbook.turn('size', w, h);
                        } catch (e) {}
                        track('zoom', {
                            meta: {
                                zoom
                            }
                        });
                    }

                    flipbookEl.style.width = baseBookWidth + 'px';
                    flipbookEl.style.height = baseBookHeight + 'px';
                    $flipbook.turn({
                        width: baseBookWidth,
                        height: baseBookHeight,
                        autoCenter: true,
                        display: sizing.display,
                        acceleration: true,
                        gradients: true,
                        duration: 850,
                    });

                    $flipbook.bind('turned', function(_e, page) {
                        const current = Number(page || 1);
                        setPageIndicator(current, totalPages);
                        track('page_turn', {
                            page_number: current,
                        });

                        if (flipSound) {
                            try {
                                flipSound.currentTime = 0;
                                flipSound.play().catch(() => {});
                            } catch (e) {}
                        }
                    });

                    track('view', {
                        page_number: 1,
                        meta: {
                            display: sizing.display
                        }
                    });

                    document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                    document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));

                    window.addEventListener('keydown', (e) => {
                        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

                        if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                            e.preventDefault();
                            $flipbook.turn('previous');
                        }
                        if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
                            e.preventDefault();
                            $flipbook.turn('next');
                        }
                    });

                    zoomOutBtn.addEventListener('click', () => applyZoom(zoom - zoomStep));
                    zoomInBtn.addEventListener('click', () => applyZoom(zoom + zoomStep));

                    function isFullscreen() {
                        return !!document.fullscreenElement;
                    }

                    function updateFullscreenLabel() {
                        const icon = isFullscreen() ? 'ki-exit-full-screen' : 'ki-screen';
                        const text = isFullscreen() ? 'Exit' : 'Fullscreen';
                        fullscreenBtn.innerHTML =
                            `<i class="ki-outline ${icon} fs-4 me-1"></i><span class="d-none d-sm-inline">${text}</span>`;
                    }

                    fullscreenBtn.addEventListener('click', async () => {
                        try {
                            if (!isFullscreen()) {
                                await document.documentElement.requestFullscreen();
                            } else {
                                await document.exitFullscreen();
                            }
                        } catch (e) {
                            toast('warning', 'Fullscreen not available in this browser.');
                        }
                        updateFullscreenLabel();
                    });

                    document.addEventListener('fullscreenchange', () => {
                        updateFullscreenLabel();
                    });
                    updateFullscreenLabel();
                }

                render();
            })();
        </script>
    @endpush

</x-default-layout>
