<x-default-layout>

    @section('title')
        Shoppable Flipbook Preview
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.slicer.preview', $pdf) }}
    @endsection

    <style>
        #flipbook {
            margin: 0 auto;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-radius: 8px;
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
            background: rgba(var(--bs-primary-rgb), 0.20);
            border-color: rgba(var(--bs-primary-rgb), 1);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }

        .toolbar-enhanced {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .toolbar-enhanced .btn {
            font-weight: 600;
        }

        .page-indicator {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            color: #333;
        }

        .share-link-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .share-link-container input {
            flex: 1;
        }

        /* Fullscreen styles */
        .fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #1e1e2d;
            z-index: 9999;
            display: flex;
            flex-direction: column;
        }

        .fullscreen-mode .fullscreen-toolbar {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        .fullscreen-mode #flipbook {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <!--begin::Toolbar-->
    <div class="toolbar-enhanced mb-8">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
            <!--begin::Navigation-->
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light" id="btnPrev">
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button type="button" class="btn btn-sm btn-light" id="btnNext">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
                <span class="page-indicator" id="pageIndicator">Page 1 of 1</span>
            </div>
            <!--end::Navigation-->

            <!--begin::Actions-->
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light" id="btnFullscreen">
                    <i class="bi bi-fullscreen"></i> Fullscreen
                </button>
                <button type="button" class="btn btn-sm btn-light" id="btnShare">
                    <i class="bi bi-share"></i> Share
                </button>
                <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left"></i> Editor
                </a>
                <a href="{{ route('catalog.pdfs.slicer.live', $pdf) }}" target="_blank" class="btn btn-sm btn-success">
                    <i class="bi bi-rocket"></i> Go Live
                </a>
            </div>
            <!--end::Actions-->
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Flipbook Card-->
    <div class="card shadow-sm mb-6">
        <!--begin::Card Header-->
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
        <!--end::Card Header-->

        <!--begin::Card Body-->
        <div class="card-body pt-0">
            <!--begin::Flipbook Container-->
            <div class="d-flex justify-content-center py-10">
                <div id="flipbook"></div>
            </div>
            <!--end::Flipbook Container-->

            <!--begin::Info Alert-->
            <div
                class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row p-5 mt-8">
                <i class="ki-outline ki-information-5 fs-2hx text-primary me-4 mb-5 mb-sm-0"></i>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <h5 class="mb-1">Interactive Hotspots</h5>
                    <span class="text-gray-700">Click on highlighted areas to open internal pages, external links,
                        product details, images, or videos.</span>
                </div>
                <button type="button"
                    class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                    data-bs-dismiss="alert">
                    <i class="ki-outline ki-cross fs-1 text-primary"></i>
                </button>
            </div>
            <!--end::Info Alert-->
        </div>
        <!--end::Card Body-->
    </div>
    <!--end::Flipbook Card-->

    <!--begin::Product Modal-->
    <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold" id="modalProductTitle">Product Details</h2>
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
                            <img id="modalProductThumb" src="" alt="" class="symbol-label rounded-3">
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
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <a href="#" target="_blank" class="btn btn-primary px-8 fw-bold" id="modalProductLink">
                        <i class="ki-outline ki-shop fs-3 me-2"></i>
                        View Product
                    </a>
                    <button type="button" class="btn btn-light px-8 fw-bold" data-bs-dismiss="modal">
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
            <div class="modal-content">
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
                        <img id="modalImageEl" src="" alt="" class="mw-100 rounded-3"
                            style="max-height: 70vh;">
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
            <div class="modal-content">
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
                    <div id="modalVideoWrap" class="ratio ratio-16x9 rounded-3 overflow-hidden"></div>
                </div>
                <!--end::Modal body-->
            </div>
        </div>
    </div>
    <!--end::Video Modal-->

    <!--begin::Share Modal-->
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
                    <div class="share-link-container">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.slicer.share', $pdf) }}">
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
    <!--end::Share Modal-->

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                const pages = @json($pages);
                const hotspots = @json($hotspots);

                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                const statusEl = document.getElementById('status');
                const flipbookEl = document.getElementById('flipbook');
                const pageIndicatorEl = document.getElementById('pageIndicator');

                let isFullscreen = false;
                let $flipbook;

                const mediaBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/hotspots'));

                const hotspotByPageId = {};
                for (const h of hotspots) {
                    const pid = String(h.catalog_pdf_page_id);
                    hotspotByPageId[pid] = hotspotByPageId[pid] || [];
                    hotspotByPageId[pid].push(h);
                }

                function setStatus(text) {
                    statusEl.innerHTML = `<i class="ki-outline ki-loading fs-6 me-1"></i>${text}`;
                }

                function updatePageIndicator() {
                    if (!$flipbook) return;
                    const current = $flipbook.turn('page');
                    const total = $flipbook.turn('pages');
                    pageIndicatorEl.textContent = `Page ${current} of ${total}`;
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

                        // Hotspots overlay
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
                                handleAction(h);
                            });
                            inner.appendChild(div);
                        }

                        pageDiv.appendChild(inner);
                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function computeTurnSize(pageViewport) {
                    const containerWidth = Math.min(1200, document.getElementById('kt_app_content_container')
                        ?.clientWidth || window.innerWidth);

                    const targetSingleWidth = Math.min(520, Math.max(320, containerWidth * 0.45));
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
                        if (idx) {
                            $flip.turn('page', idx);
                        }
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

                        setStatus(`Rendering page ${i + 1} of ${pages.length}…`);
                        const page = await pdf.getPage(p.page_number);
                        const viewport = page.getViewport({
                            scale: sizing.scale
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
                        statusEl.innerHTML =
                            '<i class="ki-outline ki-information-5 fs-6 me-1"></i>No pages initialized yet. Open the editor and initialize pages.';
                        statusEl.classList.remove('badge-light-info');
                        statusEl.classList.add('badge-light-warning');
                        return;
                    }

                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        statusEl.innerHTML =
                            '<i class="ki-outline ki-shield-cross fs-6 me-1"></i>Turn.js failed to load';
                        statusEl.classList.remove('badge-light-info');
                        statusEl.classList.add('badge-light-danger');
                        return;
                    }

                    setStatus('Building pages…');
                    buildPageShells();

                    setStatus('Loading PDF…');
                    let pdf;
                    try {
                        pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    } catch (e) {
                        console.error(e);
                        statusEl.innerHTML = '<i class="ki-outline ki-shield-cross fs-6 me-1"></i>Failed to load PDF';
                        statusEl.classList.remove('badge-light-info');
                        statusEl.classList.add('badge-light-danger');
                        return;
                    }

                    const firstPage = await pdf.getPage(pages[0].page_number);
                    const rawViewport = firstPage.getViewport({
                        scale: 1
                    });
                    const sizing = computeTurnSize(rawViewport);

                    const pageDivs = flipbookEl.querySelectorAll('.page');
                    for (const el of pageDivs) {
                        el.style.width = sizing.w + 'px';
                        el.style.height = sizing.h + 'px';
                    }

                    await renderMissingCanvases(pdf, sizing);

                    setStatus('Loading images…');
                    await waitForImages(flipbookEl);

                    statusEl.innerHTML = '<i class="ki-outline ki-check-circle fs-6 me-1"></i>Ready';
                    statusEl.classList.remove('badge-light-info');
                    statusEl.classList.add('badge-light-success');

                    try {
                        $flipbook = $('#flipbook');
                        flipbookEl.style.width = (sizing.display === 'double' ? sizing.w * 2 : sizing.w) + 'px';
                        flipbookEl.style.height = sizing.h + 'px';
                        $flipbook.turn({
                            width: sizing.display === 'double' ? sizing.w * 2 : sizing.w,
                            height: sizing.h,
                            autoCenter: true,
                            display: sizing.display,
                            acceleration: true,
                            gradients: true,
                        });

                        // Update page indicator on page turn
                        $flipbook.bind('turned', function(event, page) {
                            updatePageIndicator();
                        });

                        updatePageIndicator();

                        document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                        document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));

                        // Keyboard navigation
                        window.addEventListener('keydown', (e) => {
                            if (e.key === 'ArrowLeft') $flipbook.turn('previous');
                            if (e.key === 'ArrowRight') $flipbook.turn('next');
                            if (e.key === 'Escape' && isFullscreen) exitFullscreen();
                        });
                    } catch (e) {
                        console.error(e);
                        statusEl.innerHTML = '<i class="ki-outline ki-information-5 fs-6 me-1"></i>Flip effect failed';
                        statusEl.classList.remove('badge-light-success');
                        statusEl.classList.add('badge-light-warning');
                    }
                }

                // Fullscreen functionality
                function enterFullscreen() {
                    const card = document.querySelector('.card');
                    const toolbar = document.querySelector('.toolbar-enhanced');

                    isFullscreen = true;
                    card.classList.add('fullscreen-mode');

                    // Create fullscreen toolbar
                    const fsToolbar = document.createElement('div');
                    fsToolbar.className = 'fullscreen-toolbar';
                    fsToolbar.innerHTML = `
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-light" id="btnPrevFs">Previous</button>
                            <button type="button" class="btn btn-sm btn-light" id="btnNextFs">Next</button>
                            <span id="pageIndicatorFs" style="margin-left: 15px;"></span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-light" id="btnExitFullscreen">
                                <i class="bi bi-fullscreen-exit"></i> Exit Fullscreen
                            </button>
                        </div>
                    `;

                    card.insertBefore(fsToolbar, card.firstChild);
                    toolbar.style.display = 'none';

                    // Add event listeners
                    document.getElementById('btnPrevFs').addEventListener('click', () => $flipbook.turn('previous'));
                    document.getElementById('btnNextFs').addEventListener('click', () => $flipbook.turn('next'));
                    document.getElementById('btnExitFullscreen').addEventListener('click', exitFullscreen);

                    // Update page info in fullscreen toolbar
                    $flipbook.bind('turned.fullscreen', function() {
                        const current = $flipbook.turn('page');
                        const total = $flipbook.turn('pages');
                        document.getElementById('pageIndicatorFs').textContent = `Page ${current} of ${total}`;
                    });

                    // Trigger initial update
                    const current = $flipbook.turn('page');
                    const total = $flipbook.turn('pages');
                    document.getElementById('pageIndicatorFs').textContent = `Page ${current} of ${total}`;
                }

                function exitFullscreen() {
                    const card = document.querySelector('.card');
                    const toolbar = document.querySelector('.toolbar-enhanced');
                    const fsToolbar = card.querySelector('.fullscreen-toolbar');

                    isFullscreen = false;
                    card.classList.remove('fullscreen-mode');

                    if (fsToolbar) {
                        fsToolbar.remove();
                    }

                    toolbar.style.display = 'flex';
                    $flipbook.unbind('turned.fullscreen');
                }

                // Share functionality
                document.getElementById('btnShare').addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                    shareModal.show();
                });

                document.getElementById('btnCopyLink').addEventListener('click', function() {
                    const linkInput = document.getElementById('shareLink');
                    linkInput.select();
                    linkInput.setSelectionRange(0, 99999);

                    navigator.clipboard.writeText(linkInput.value).then(function() {
                        document.getElementById('copySuccess').style.display = 'block';
                        setTimeout(function() {
                            document.getElementById('copySuccess').style.display = 'none';
                        }, 3000);
                    }).catch(function(err) {
                        alert('Failed to copy link: ' + err);
                    });
                });

                document.getElementById('btnFullscreen').addEventListener('click', enterFullscreen);

                render();
            })();
        </script>
    @endpush

</x-default-layout>
