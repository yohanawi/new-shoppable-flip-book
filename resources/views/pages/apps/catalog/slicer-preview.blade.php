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
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
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
        }
    </style>

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack gap-4 mb-8">

        <!--begin::Actions-->
        <div class="d-flex flex-wrap gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.slicer.edit', $pdf) }}"
                class="btn btn-sm btn-light-primary btn-active-primary fw-bold">
                <i class="ki-outline ki-left fs-4 me-1"></i>
                Back to Editor
            </a>
            <a href="{{ route('catalog.pdfs.slicer.live', $pdf) }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                <i class="ki-outline ki-rocket fs-4 me-1"></i>
                Go Live
                <i class="ki-outline ki-arrow-right fs-4 ms-1"></i>
            </a>
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Flipbook Card-->
    <div class="card shadow-sm mb-6">
        <!--begin::Card Header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <!--begin::Controls-->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-icon btn-light-primary" id="btnPrev">
                        <i class="ki-outline ki-left fs-3"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light-primary" id="btnNext">
                        <i class="ki-outline ki-right fs-3"></i>
                    </button>
                    <div class="separator separator-vertical mx-2 h-30px"></div>
                    <div class="badge badge-light-info fs-7 fw-bold" id="status">
                        <i class="ki-outline ki-loading fs-6 me-1"></i>
                        Loading…
                    </div>
                </div>
                <!--end::Controls-->
            </div>
            <div class="card-toolbar">
                <a class="btn btn-sm btn-light fw-bold" href="{{ route('catalog.pdfs.show', $pdf) }}">
                    <i class="ki-outline ki-document fs-4 me-1"></i>
                    PDF Details
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
                        const $flipbook = $('#flipbook');
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

                        document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                        document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));
                    } catch (e) {
                        console.error(e);
                        statusEl.innerHTML = '<i class="ki-outline ki-information-5 fs-6 me-1"></i>Flip effect failed';
                        statusEl.classList.remove('badge-light-success');
                        statusEl.classList.add('badge-light-warning');
                    }
                }

                render();
            })();
        </script>
    @endpush

</x-default-layout>
