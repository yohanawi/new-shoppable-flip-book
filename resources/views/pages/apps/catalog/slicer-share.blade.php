<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pdf->title }} - Shoppable Flipbook</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            height: 100vh;
        }

        .share-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .share-toolbar {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .share-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .share-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        #flipbook {
            margin: 0 auto;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
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
            background: rgba(var(--bs-primary-rgb), 0.22);
            border-color: rgba(var(--bs-primary-rgb), 1);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.3);
        }

        .page-info {
            color: #fff;
            font-size: 14px;
            margin-left: 15px;
            font-weight: 600;
        }

        .loading-message {
            color: #fff;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-toolbar {
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-toolbar:hover {
            transform: translateY(-2px);
        }

        /* Modal Button Hover Effects */
        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="share-container">
        <div class="share-toolbar">
            <div class="d-flex align-items-center">
                <h1 class="share-title">{{ $pdf->title }}</h1>
                <span class="page-info" id="pageInfo">Loading...</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light btn-toolbar" id="btnPrev">
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button type="button" class="btn btn-sm btn-light btn-toolbar" id="btnNext">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
                <a class="btn btn-sm btn-primary btn-toolbar" href="{{ route('catalog.pdfs.download', $pdf) }}">
                    <i class="bi bi-download"></i> Download
                </a>
            </div>
        </div>

        <div class="share-content">
            <div id="flipbook"></div>
            <div class="loading-message" id="loadingMessage">Loading shoppable flipbook...</div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: none; overflow: hidden;">
                <!-- Gradient Header -->
                <div class="modal-header border-0 pb-0"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px 30px;">
                    <h2 class="fw-bold text-white mb-0" id="modalProductTitle"
                        style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">  Details</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-6 pb-8 px-10">
                    <div class="d-flex align-items-start gap-5 flex-column flex-md-row">
                        <!-- Product Image with Frame -->
                        <div class="flex-shrink-0 position-relative" id="modalProductThumbWrapper"
                            style="display:none;">
                            <div
                                style="width: 180px; height: 180px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 16px; padding: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
                                <img id="modalProductThumb" src="" alt=""
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                            </div>
                        </div>
                        <!-- Product Info -->
                        <div class="flex-grow-1">
                            <div class="fs-2 fw-bold mb-3" id="modalProductName"
                                style="color: #2d3748; line-height: 1.3;"></div>
                            <div class="fs-6 mb-5" id="modalProductDesc" style="color: #718096; line-height: 1.6;">
                            </div>
                            <div class="d-flex align-items-center mb-4">
                                <div class="badge"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 1.5rem; padding: 10px 20px; border-radius: 10px; font-weight: 700;"
                                    id="modalProductPrice"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Enhanced Footer -->
                <div class="modal-footer border-0 pt-0 justify-content-center pb-8"
                    style="background: linear-gradient(to bottom, transparent 0%, #f7fafc 100%);">
                    <a href="#" target="_blank" class="btn px-12 py-3 fw-bold" id="modalProductLink"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); transition: all 0.3s ease;">
                        <i class="bi bi-cart3 me-2"></i> View Product
                    </a>
                    <button type="button" class="btn btn-light px-12 py-3 fw-bold" data-bs-dismiss="modal"
                        style="border-radius: 12px; border: 2px solid #e2e8f0;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="modalImage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg"
                style="border-radius: 20px; border: none; overflow: hidden; background: #000;">
                <!-- Dark Header -->
                <div class="modal-header border-0 pb-0"
                    style="background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); padding: 20px 30px;">
                    <h2 class="fw-bold text-white mb-0" id="modalImageTitle">Image Preview</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-8" style="background: #000;">
                    <div class="text-center position-relative">
                        <img id="modalImageEl" src="" alt="" class="rounded-3"
                            style="max-width: 100%; max-height: 75vh; box-shadow: 0 20px 60px rgba(255,255,255,0.1); border: 3px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="modalVideo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg"
                style="border-radius: 20px; border: none; overflow: hidden; background: linear-gradient(135deg, #1e1e2d 0%, #2d2d44 100%);">
                <!-- Video Header with Gradient -->
                <div class="modal-header border-0 pb-0"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px;">
                    <h2 class="fw-bold text-white mb-0" id="modalVideoTitle"
                        style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);"><i
                            class="bi bi-play-circle-fill me-2"></i>Video Player</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-8">
                    <div id="modalVideoWrap" class="ratio ratio-16x9 rounded-3 overflow-hidden"
                        style="box-shadow: 0 20px 60px rgba(0,0,0,0.4); border: 3px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

            const flipbookEl = document.getElementById('flipbook');
            const pageInfoEl = document.getElementById('pageInfo');
            const loadingMessageEl = document.getElementById('loadingMessage');

            const mediaBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/hotspots'));

            let $flipbook;

            const hotspotByPageId = {};
            for (const h of hotspots) {
                const pid = String(h.catalog_pdf_page_id);
                hotspotByPageId[pid] = hotspotByPageId[pid] || [];
                hotspotByPageId[pid].push(h);
            }

            function updatePageInfo() {
                if (!$flipbook) return;
                const current = $flipbook.turn('page');
                const total = $flipbook.turn('pages');
                pageInfoEl.textContent = `Page ${current} of ${total}`;
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
                        img.alt = p.title || `Page ${p.page_number}`;
                        inner.appendChild(img);
                    } else {
                        const canvas = document.createElement('canvas');
                        canvas.width = 10;
                        canvas.height = 10;
                        inner.appendChild(canvas);
                    }

                    // Add hotspots
                    const hots = hotspotByPageId[String(p.id)] || [];
                    for (const h of hots) {
                        const hs = document.createElement('div');
                        hs.className = 'hotspot';
                        hs.style.left = ((h.x || 0) * 100) + '%';
                        hs.style.top = ((h.y || 0) * 100) + '%';
                        hs.style.width = ((h.w || 0) * 100) + '%';
                        hs.style.height = ((h.h || 0) * 100) + '%';
                        hs.addEventListener('click', () => handleAction(h));
                        inner.appendChild(hs);
                    }

                    pageDiv.appendChild(inner);
                    flipbookEl.appendChild(pageDiv);
                }
            }

            function computeTurnSize(pageViewport) {
                const containerWidth = Math.min(window.innerWidth - 40, 1400);
                const containerHeight = window.innerHeight - 120;

                const scaleWidth = (containerWidth * 0.45) / pageViewport.width;
                const scaleHeight = containerHeight / pageViewport.height;
                const scale = Math.min(scaleWidth, scaleHeight);

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
                    if (img.complete && img.naturalWidth > 0) return Promise.resolve();
                    return new Promise((resolve) => {
                        img.onload = () => resolve();
                        img.onerror = () => resolve();
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
                if (h.action_type === 'internal_page') {
                    const targetIdx = pageIndexForPageNumber(h.target_page_number);
                    if (targetIdx && $flipbook) {
                        $flipbook.turn('page', targetIdx);
                    }
                }

                if (h.action_type === 'external_link') {
                    if (h.link_url) window.open(h.link_url, '_blank');
                }

                if (h.action_type === 'popup_window') {
                    document.getElementById('modalProductTitle').textContent = h.link_text || h.product_name ||
                        'Product';
                    document.getElementById('modalProductName').textContent = h.product_name || '';
                    document.getElementById('modalProductDesc').textContent = h.product_description || '';
                    document.getElementById('modalProductPrice').textContent = h.product_price || '';
                    document.getElementById('modalProductLink').href = h.link_url || '#';

                    if (h.thumbnail_path) {
                        document.getElementById('modalProductThumbWrapper').style.display = 'block';
                        document.getElementById('modalProductThumb').src = mediaUrl(h.id, 'thumbnail');
                    } else {
                        document.getElementById('modalProductThumbWrapper').style.display = 'none';
                    }

                    showModal('modalProduct');
                }

                if (h.action_type === 'popup_image') {
                    document.getElementById('modalImageTitle').textContent = 'Image Preview';
                    document.getElementById('modalImageEl').src = mediaUrl(h.id, 'popup_image');
                    showModal('modalImage');
                }

                if (h.action_type === 'popup_video') {
                    document.getElementById('modalVideoTitle').textContent = 'Video Player';
                    const wrap = document.getElementById('modalVideoWrap');

                    if (h.popup_video_url) {
                        wrap.innerHTML =
                            `<iframe src="${h.popup_video_url}" frameborder="0" allow="autoplay; fullscreen" allowfullscreen class="w-100 h-100"></iframe>`;
                    } else if (h.popup_video_path) {
                        wrap.innerHTML =
                            `<video controls class="w-100 h-100"><source src="${mediaUrl(h.id, 'popup_video')}" type="video/mp4">Your browser does not support video.</video>`;
                    }

                    showModal('modalVideo');
                }
            }

            async function renderMissingCanvases(pdf, sizing) {
                const pageDivs = flipbookEl.querySelectorAll('.page');
                for (let i = 0; i < pages.length; i++) {
                    const p = pages[i];
                    if (p.image_path) continue;

                    const pageDiv = pageDivs[i];
                    const canvas = pageDiv.querySelector('canvas');
                    if (!canvas) continue;

                    const page = await pdf.getPage(p.page_number);
                    const viewport = page.getViewport({
                        scale: sizing.scale
                    });

                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);

                    const ctx = canvas.getContext('2d');
                    await page.render({
                        canvasContext: ctx,
                        viewport
                    }).promise;
                }
            }

            async function render() {
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                    loadingMessageEl.textContent = 'Turn.js failed to load.';
                    return;
                }

                buildPageShells();

                let pdf;
                try {
                    pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                } catch (e) {
                    console.error(e);
                    loadingMessageEl.textContent = 'Failed to load PDF.';
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
                await waitForImages(flipbookEl);

                loadingMessageEl.style.display = 'none';

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

                    $flipbook.bind('turned', function() {
                        updatePageInfo();
                    });

                    updatePageInfo();

                    document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                    document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));

                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowLeft') $flipbook.turn('previous');
                        if (e.key === 'ArrowRight') $flipbook.turn('next');
                    });
                } catch (e) {
                    console.error(e);
                    loadingMessageEl.textContent = 'Failed to initialize flipbook.';
                    loadingMessageEl.style.display = 'block';
                }
            }

            render();
        })();
    </script>
</body>

</html>
