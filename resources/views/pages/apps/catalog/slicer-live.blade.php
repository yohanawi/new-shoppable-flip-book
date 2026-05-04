<x-default-layout>

    @section('title')
        Live Shoppable Flipbook
    @endsection

    @section('breadcrumbs')

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
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mw-900px">
                <div class="modal-content rounded-4 border-0 shadow-sm overflow-hidden">
                    <div class="modal-header border-0 pb-0 px-6 px-lg-8 pt-6 pt-lg-8">
                        <div class="d-flex align-items-start justify-content-between gap-4 w-100">
                            <div class="min-w-0">
                                <h2 class="fw-bolder text-dark mb-0 fs-2x" id="modalProductTitle">Product Details</h2>
                            </div>
                            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary"
                                data-bs-dismiss="modal" aria-label="Close">
                                <i class="bi bi-x-lg fs-2"></i>
                            </button>
                        </div>
                    </div>

                    <div class="modal-body px-6 px-lg-8 py-6">
                        <div class="row g-6 align-items-stretch">
                            <div class="col-12 col-lg-4 d-none" id="modalProductThumbWrapper">
                                <div class="card card-flush border-0 bg-light-primary h-100 shadow-sm">
                                    <div class="card-body p-4">
                                        <img id="modalProductThumb" src="" alt=""
                                            class="img-fluid rounded-3 w-100 h-250px h-lg-300px object-fit-cover">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-8">
                                <div class="card border-0 bg-light h-100 shadow-sm">
                                    <div class="card-body p-5 p-lg-7 d-flex flex-column gap-5">
                                        <div class="d-flex flex-column gap-3">
                                            <h3 class="text-dark fw-bolder mb-0 fs-1" id="modalProductName"></h3>
                                            <p class="text-muted fs-5 lh-lg mb-0" id="modalProductDesc">
                                                Product details will appear here when available.
                                            </p>
                                        </div>

                                        <div class="card border-0 mt-auto d-none" id="modalProductPriceRow">
                                            <div
                                                class="card-body p-4 d-flex align-items-center justify-content-between gap-4 flex-wrap">
                                                <div>
                                                    <div
                                                        class="text-black text-opacity-75 text-uppercase fw-bolder fs-8 mb-1">
                                                        Price</div>
                                                    <div class="text-black fw-bolder fs-1" id="modalProductPrice"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-transparent px-6 px-lg-8 pb-6 pt-0 justify-content-end gap-3">
                        <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary d-none"
                            id="modalProductLink">
                            <i class="bi bi-bag-check me-2"></i>
                            View Product
                        </a>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Product Modal-->

        <!--begin::Image Modal-->
        <div class="modal fade" id="modalImage" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content shadow-lg"
                    style="border-radius: 20px; border: none; overflow: hidden; background: #000;">
                    <!--begin::Dark Header-->
                    <div class="modal-header border-0 pb-0"
                        style="background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); padding: 20px 30px;">
                        <h2 class="fw-bold text-white mb-0" id="modalImageTitle">Image Preview</h2>
                        <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"
                            style="background: rgba(255,255,255,0.1); border-radius: 8px;">
                            <i class="ki-outline ki-cross fs-1 text-white"></i>
                        </div>
                    </div>
                    <!--end::Dark Header-->

                    <!--begin::Modal body-->
                    <div class="modal-body p-8" style="background: #000;">
                        <div class="text-center position-relative">
                            <img id="modalImageEl" src="" alt="" class="rounded-3"
                                style="max-width: 100%; max-height: 75vh; box-shadow: 0 20px 60px rgba(255,255,255,0.1); border: 3px solid rgba(255,255,255,0.1);">
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
                <div class="modal-content shadow-lg"
                    style="border-radius: 20px; border: none; overflow: hidden; background: linear-gradient(135deg, #1e1e2d 0%, #2d2d44 100%);">
                    <!--begin::Video Header with Gradient-->
                    <div class="modal-header border-0 pb-0"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px;">
                        <h2 class="fw-bold text-white mb-0" id="modalVideoTitle"
                            style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);"><i
                                class="ki-outline ki-video fs-2 me-2"></i>Video Player</h2>
                        <div class="btn btn-sm btn-icon" data-bs-dismiss="modal"
                            style="background: rgba(255,255,255,0.2); border-radius: 8px;">
                            <i class="ki-outline ki-cross fs-1 text-white"></i>
                        </div>
                    </div>
                    <!--end::Video Header with Gradient-->

                    <!--begin::Modal body-->
                    <div class="modal-body p-8">
                        <div id="modalVideoWrap" class="ratio ratio-16x9 rounded-3 overflow-hidden"
                            style="box-shadow: 0 20px 60px rgba(0,0,0,0.4); border: 3px solid rgba(255,255,255,0.1);">
                        </div>
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
                    const modalProductEl = document.getElementById('modalProduct');
                    const modalProductTitleEl = document.getElementById('modalProductTitle');
                    const modalProductNameEl = document.getElementById('modalProductName');
                    const modalProductDescEl = document.getElementById('modalProductDesc');
                    const modalProductPriceEl = document.getElementById('modalProductPrice');
                    const modalProductPriceRowEl = document.getElementById('modalProductPriceRow');
                    const modalProductThumbWrapperEl = document.getElementById('modalProductThumbWrapper');
                    const modalProductThumbEl = document.getElementById('modalProductThumb');
                    const modalProductLinkEl = document.getElementById('modalProductLink');
                    const modalVideoEl = document.getElementById('modalVideo');
                    const modalVideoWrapEl = document.getElementById('modalVideoWrap');
                    let viewportBase = null;
                    let resizeFrame = null;

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
                                const trimmedColor = String(h.color ?? '').trim();
                                if (trimmedColor && window.CSS?.supports?.('color', trimmedColor)) {
                                    div.classList.add('has-color');
                                    div.style.setProperty('--hotspot-border', trimmedColor);
                                    div.style.setProperty('--hotspot-fill', 'transparent');
                                    div.style.setProperty('--hotspot-fill-hover', 'transparent');
                                    div.style.setProperty('--hotspot-shadow', 'transparent');
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
                        const containerWidth = Math.min(
                            document.fullscreenElement ? window.innerWidth : 1300,
                            Math.max(320, (document.fullscreenElement ? window.innerWidth : document.getElementById(
                                'kt_app_content_container')?.clientWidth) || window.innerWidth),
                        );
                        const containerHeight = Math.max(420, (document.fullscreenElement ? window.innerHeight - 170 :
                            window.innerHeight - 260));

                        const isMobile = window.innerWidth < 992;
                        const widthRatio = isMobile ? 0.88 : 0.48;
                        const maxWidthByHeight = Math.floor((containerHeight * pageViewport.width) / pageViewport.height);
                        const targetSingleWidth = Math.min(640, maxWidthByHeight, Math.max(360, containerWidth *
                            widthRatio));
                        const scale = targetSingleWidth / pageViewport.width;
                        const w = Math.floor(pageViewport.width * scale);
                        const h = Math.floor(pageViewport.height * scale);

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
                        if (!el) {
                            console.error('Modal element not found: ' + modalId);
                            return;
                        }

                        // Use jQuery Bootstrap modal method (more compatible with Laravel's Bootstrap setup)
                        if (window.$ && typeof window.$.fn.modal === 'function') {
                            $(el).modal('show');
                        } else if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                            window.jQuery(el).modal('show');
                        } else {
                            // Fallback to vanilla Bootstrap
                            try {
                                let modalInstance;
                                if (typeof bootstrap !== 'undefined') {
                                    modalInstance = new bootstrap.Modal(el);
                                } else if (window.bootstrap) {
                                    modalInstance = new window.bootstrap.Modal(el);
                                } else if (window.Bootstrap) {
                                    modalInstance = new window.Bootstrap.Modal(el);
                                } else {
                                    console.error('Bootstrap Modal not available');
                                    return;
                                }
                                modalInstance.show();
                            } catch (e) {
                                console.error('Failed to show modal:', e);
                            }
                        }
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
                    modalProductEl?.addEventListener('hidden.bs.modal', resetProductModal);

                    function resetProductModal() {
                        modalProductTitleEl.textContent = 'Product Details';
                        modalProductNameEl.textContent = '';
                        modalProductDescEl.textContent = '';
                        modalProductPriceEl.textContent = '';
                        modalProductPriceRowEl.style.display = 'none';
                        modalProductThumbEl.removeAttribute('src');
                        modalProductThumbEl.alt = '';
                        modalProductThumbWrapperEl.style.display = 'none';
                        modalProductLinkEl.href = '#';
                        modalProductLinkEl.style.display = 'none';
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
                            resetProductModal();

                            const title = String(h.title || '').trim();
                            const description = String(h.description || '').trim();
                            const price = String(h.price || '').trim();
                            const link = String(h.link || '').trim();

                            modalProductTitleEl.textContent = title || 'Product Details';
                            modalProductNameEl.textContent = title || 'Catalog Product';
                            modalProductDescEl.textContent = description;

                            if (price) {
                                modalProductPriceEl.textContent = price;
                                modalProductPriceRowEl.style.display = 'flex';
                            }

                            if (h.thumbnail_path) {
                                modalProductThumbEl.src = mediaUrl(h.id, 'thumbnail');
                                modalProductThumbEl.alt = title || 'Product thumbnail';
                                modalProductThumbWrapperEl.style.display = '';
                            }

                            if (link) {
                                modalProductLinkEl.href = link;
                                modalProductLinkEl.style.display = '';
                            }

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
                        viewportBase = firstPage.getViewport({
                            scale: 1
                        });
                        const sizing = computeTurnSize(viewportBase);

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

                        const flipSoundUrl = @json(asset('assets/media/sounds/page-flip-new.mp3'));
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
                            const current = Number(pageNumber || ($flipbook ? $flipbook.turn('page') : 1) || 1);
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

                        try {
                            flipSound = createFlipSound();
                        } catch (e) {
                            flipSound = null;
                        }

                        const $flipbook = $('#flipbook');
                        let baseBookWidth = (sizing.display === 'double' ? sizing.w * 2 : sizing.w);
                        let baseBookHeight = sizing.h;
                        lastTurnedPage = 1;

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

                        function applyBookLayout() {
                            if (!viewportBase) {
                                return;
                            }

                            const sizing = computeTurnSize(viewportBase);
                            const currentPage = $flipbook.turn('page');
                            baseBookWidth = sizing.display === 'double' ? sizing.w * 2 : sizing.w;
                            baseBookHeight = sizing.h;

                            const w = Math.floor(baseBookWidth * zoom);
                            const h = Math.floor(baseBookHeight * zoom);

                            flipbookEl.style.width = w + 'px';
                            flipbookEl.style.height = h + 'px';

                            try {
                                $flipbook.turn('display', sizing.display);
                            } catch (e) {}

                            try {
                                $flipbook.turn('size', w, h);
                            } catch (e) {}

                            try {
                                $flipbook.turn('page', currentPage);
                            } catch (e) {}
                        }

                        function scheduleBookLayout() {
                            if (resizeFrame) {
                                window.cancelAnimationFrame(resizeFrame);
                            }

                            resizeFrame = window.requestAnimationFrame(() => {
                                applyBookLayout();
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
                            playFlipSoundIfPageChanged(current);
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
                            scheduleBookLayout();
                        });
                        window.addEventListener('resize', scheduleBookLayout);
                        updateFullscreenLabel();
                    }

                    render();
                })();
            </script>
        @endpush

    </x-default-layout>
