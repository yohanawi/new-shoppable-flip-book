<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pdf->title }} - Shared PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --share-bg-top: {{ $shareAppearance['backgroundColor'] ?? '#08111f' }};
            --share-bg-bottom: {{ $shareAppearance['backgroundColor'] ?? '#133a66' }};
            --share-toolbar-bg: {{ $shareAppearance['toolbarBackgroundColor'] ?? '#020617' }};
            --share-panel-border: rgba(255, 255, 255, 0.12);
            --share-text: #eef4ff;
            --share-muted: rgba(238, 244, 255, 0.72);
            --share-accent: #6ee7f2;
            --share-accent-strong: #25b8cf;
            --share-shadow: 0 28px 80px rgba(0, 0, 0, 0.42);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background:
                radial-gradient(circle at top left, rgba(110, 231, 242, 0.14), transparent 30%),
                radial-gradient(circle at bottom right, rgba(56, 189, 248, 0.18), transparent 28%),
                linear-gradient(160deg, var(--share-bg-top) 0%, var(--share-bg-bottom) 100%);
            color: var(--share-text);
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
            height: 100vh;
        }

        .share-shell {
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            isolation: isolate;
        }

        .share-background-media,
        .share-background-media img,
        .share-background-media video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .share-background-media {
            z-index: 0;
            overflow: hidden;
        }

        .share-background-media img,
        .share-background-media video {
            object-fit: cover;
            filter: saturate(1.08);
        }

        .share-background-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.3), rgba(2, 6, 23, 0.62));
        }

        .share-shell::before,
        .share-shell::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(40px);
            opacity: 0.45;
        }

        .share-shell::before {
            inset: 80px auto auto 5%;
            width: 260px;
            height: 260px;
            background: rgba(110, 231, 242, 0.24);
        }

        .share-shell::after {
            inset: auto 4% 70px auto;
            width: 320px;
            height: 320px;
            background: rgba(37, 99, 235, 0.24);
        }

        .share-toolbar {
            background: var(--share-toolbar-bg);
            backdrop-filter: blur(16px);
            padding: 18px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--share-text);
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .share-title {
            font-size: clamp(20px, 2vw, 28px);
            font-weight: 800;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .share-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .share-badge {
            background: rgba(255, 255, 255, 0.1);
            color: var(--share-text);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .share-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 34px 34px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .share-stage {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: stretch;
            justify-content: center;
            position: relative;
        }

        .share-book-area {
            flex: 1;
            min-width: 0;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .share-book-frame {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        #flipbook {
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.3);
            border-radius: 14px;
        }

        .page-inner {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            cursor: grab;
            user-select: none;
            touch-action: pan-y;
        }

        .page-inner.is-dragging {
            cursor: grabbing;
        }

        .page-inner img,
        .page-inner canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .hotspot {
            --hotspot-surface-top: rgba(255, 255, 255, 0.10);
            --hotspot-surface-bottom: rgba(226, 232, 240, 0.08);
            --hotspot-surface-top-hover: rgba(255, 255, 255, 0.18);
            --hotspot-surface-bottom-hover: rgba(226, 232, 240, 0.14);
            --hotspot-highlight: rgba(255, 255, 255, 0.72);
            --hotspot-edge: rgba(148, 163, 184, 0.22);
            --hotspot-shadow: rgba(148, 163, 184, 0.28);
            --hotspot-shadow-hover: rgba(148, 163, 184, 0.34);
            --hotspot-cast-shadow: rgba(15, 23, 42, 0.12);
            --hotspot-cast-shadow-hover: rgba(15, 23, 42, 0.16);
            position: absolute;
            border: none;
            background: linear-gradient(145deg, var(--hotspot-surface-top), var(--hotspot-surface-bottom));
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            box-shadow: inset 1px 1px 0 var(--hotspot-highlight), inset -1px -1px 0 var(--hotspot-edge),
                0 10px 22px var(--hotspot-shadow), 0 2px 6px var(--hotspot-cast-shadow);
        }

        .hotspot.has-color {}

        .hotspot:hover {
            background: linear-gradient(145deg, var(--hotspot-surface-top-hover), var(--hotspot-surface-bottom-hover));
            transform: translateY(-1px) scale(1.01);
            box-shadow: inset 1px 1px 0 rgba(255, 255, 255, 0.82), inset -1px -1px 0 var(--hotspot-edge),
                0 14px 28px var(--hotspot-shadow-hover), 0 2px 6px var(--hotspot-cast-shadow-hover);
        }

        .share-side-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 68px;
            height: 68px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(5, 12, 24, 0.72);
            color: var(--share-text);
            backdrop-filter: blur(12px);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
            z-index: 3;
        }

        .share-side-button:hover {
            transform: translateY(-50%) scale(1.04);
            background: rgba(14, 24, 44, 0.84);
            border-color: rgba(110, 231, 242, 0.4);
        }

        .share-side-button:active {
            transform: translateY(-50%) scale(0.98);
        }

        .share-side-button-prev {
            left: 12px;
        }

        .share-side-button-next {
            right: 12px;
        }

        .share-side-button-label {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--share-muted);
        }

        .loading-message {
            color: var(--share-text);
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 4;
            background: rgba(3, 8, 18, 0.24);
            backdrop-filter: blur(6px);
        }

        .share-hint {
            position: absolute;
            left: 50%;
            bottom: 8px;
            transform: translateX(-50%);
            color: var(--share-muted);
            font-size: 13px;
            letter-spacing: 0.04em;
            z-index: 3;
            text-align: center;
        }

        .share-hint strong {
            color: var(--share-text);
            font-weight: 700;
        }

        .share-brand-badge {
            position: absolute;
            z-index: 4;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border-radius: 20px;
            background: rgba(2, 6, 23, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 0 22px 50px rgba(2, 6, 23, 0.35);
            backdrop-filter: blur(14px);
            color: var(--share-text);
        }

        .share-brand-badge img {
            display: block;
            max-width: 100%;
            height: auto;
        }

        .share-brand-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        @media (max-width: 991.98px) {
            .share-content {
                padding: 18px 16px 20px;
            }

            .share-stage {
                padding: 14px 12px 18px;
            }

            .share-book-area {
                padding: 12px 0 56px;
            }

            .share-side-button {
                top: auto;
                bottom: 0;
                transform: none;
                width: 60px;
                height: 60px;
            }

            .share-side-button:hover,
            .share-side-button:active {
                transform: scale(1.02);
            }

            .share-side-button-prev {
                left: calc(50% - 76px);
            }

            .share-side-button-next {
                right: calc(50% - 76px);
            }

            .share-side-button-label {
                display: none;
            }

            .share-hint {
                bottom: 68px;
                font-size: 12px;
                width: calc(100% - 32px);
            }

            .share-brand-badge {
                max-width: calc(100% - 24px);
            }

        }

        @media (max-width: 767.98px) {}
    </style>
</head>

<body>
    <div class="share-shell">
        @if (($shareAppearance['backgroundType'] ?? 'color') === 'image' && !empty($shareAppearance['backgroundImageUrl']))
            <div class="share-background-media" aria-hidden="true">
                <img src="{{ $shareAppearance['backgroundImageUrl'] }}" alt="">
            </div>
        @elseif (($shareAppearance['backgroundType'] ?? 'color') === 'video' && !empty($shareAppearance['backgroundVideoUrl']))
            <div class="share-background-media" aria-hidden="true">
                <video src="{{ $shareAppearance['backgroundVideoUrl'] }}" autoplay muted loop playsinline></video>
            </div>
        @endif

        @if (!empty($shareAppearance['hasBranding']))
            <div class="share-brand-badge"
                style="left: {{ $shareAppearance['logoPositionX'] ?? 8 }}%; top: {{ $shareAppearance['logoPositionY'] ?? 8 }}%; width: {{ $shareAppearance['logoWidth'] ?? 168 }}px;">
                @if (!empty($shareAppearance['logoUrl']))
                    <div>
                        <img src="{{ $shareAppearance['logoUrl'] }}" alt="Logo">
                    </div>
                @endif
                @if (!empty($shareAppearance['logoTitle']))
                    <div class="share-brand-title">{{ $shareAppearance['logoTitle'] }}</div>
                @endif
            </div>
        @endif

        @if ($shareAppearance['toolbarVisible'] ?? true)
            <div class="share-toolbar">
                <div>
                    <h1 class="share-title">{{ $pdf->title }}</h1>
                    <div class="share-meta">
                        <span class="share-badge" id="pageInfo">Loading...</span>
                        @if (!empty($shareAppearance['hasBranding']))
                            <span class="share-badge">Branded preview</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="share-content">
            <div class="share-stage">
                <button type="button" class="share-side-button share-side-button-prev" id="btnPrev"
                    aria-label="Previous page">
                    <i class="bi bi-chevron-left fs-1"></i>
                    <span class="share-side-button-label">Previous</span>
                </button>

                <div class="share-book-area">
                    <div class="share-book-frame">
                        <div id="flipbook"></div>
                        <div class="loading-message" id="loadingMessage">Loading shared PDF...</div>
                    </div>
                </div>
            </div>

            <button type="button" class="share-side-button share-side-button-next" id="btnNext"
                aria-label="Next page">
                <i class="bi bi-chevron-right fs-1"></i>
                <span class="share-side-button-label">Next</span>
            </button>
        </div>
    </div>

    <div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="modal-header border-0 bg-dark bg-gradient text-white px-4 px-lg-5 py-3 position-relative">
                    <div class="w-100 pe-5">
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="bg-white text-dark d-inline-flex align-items-center justify-content-center shadow-sm p-3 flex-shrink-0 rounded-4">
                                <i class="bi bi-bag-heart fs-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="h2 mb-1 fw-bold text-white" id="modalProductTitle">Product Details</h2>
                                <p class="mb-0 text-white-50">Discover featured product details inside this interactive
                                    catalog.</p>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 mt-4 me-4"
                        data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light px-4 px-lg-5 py-4 py-lg-5">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-12 col-lg-4 d-none" id="modalProductThumbWrapper">
                            <div class="card border-0 h-100 overflow-hidden rounded-4 bg-white">
                                <div class="card-body p-0 h-100 d-flex align-items-stretch justify-content-center">
                                    <img id="modalProductThumb" src="" alt=""
                                        class="img-fluid rounded-4 w-100 h-100 object-fit-cover">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-12" id="modalProductInfoColumn">
                            <div class="card border-0 h-100 rounded-4 bg-white">
                                <div class="card-body p-4 p-lg-5 d-flex flex-column gap-4">
                                    <div>
                                        <div class="text-uppercase text-primary fw-semibold small mb-2">Product overview
                                        </div>
                                        <h3 class="display-6 fw-bold text-dark mb-3" id="modalProductName"></h3>
                                        <p class="text-secondary lh-lg mb-0" id="modalProductDesc">
                                            Product details will appear here when available.
                                        </p>
                                    </div>
                                    <div id="modalProductPriceRow" class="d-none px-0">
                                        <div class="d-flex align-items-center gap-4 py-3 px-4 flex-wrap justify-content-start"
                                            style="backdrop-filter: blur(8px); background: rgba(30, 34, 90, 0.18); border-radius: 2rem; box-shadow: 0 2px 24px 0 rgba(30,34,90,0.10);">
                                            <div class="d-flex align-items-center justify-content-center me-2"
                                                style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #fff 60%, #e9e9f7 100%); box-shadow: 0 2px 8px 0 rgba(30,34,90,0.10);">
                                                <i class="bi bi-tag fs-3 text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div
                                                    class="text-uppercase fw-semibold small mb-1 text-primary opacity-75">
                                                    Price</div>
                                                <div class="fs-2 fw-bold mb-0 text-dark" id="modalProductPrice"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white px-4 px-lg-5 pb-4 pb-lg-5 pt-0">
                    <div class="d-flex flex-column flex-md-row justify-content-end align-items-md-center gap-3 w-100">
                        <div class="d-flex flex-column flex-sm-row justify-content-sm-end align-items-stretch gap-2">
                            <a href="#" target="_blank" rel="noopener noreferrer"
                                class="btn btn-primary d-none" id="modalProductLink">
                                <i class="bi bi-cart3 me-2"></i> View Product
                            </a>
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg bg-black">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold text-white mb-0" id="modalImageTitle">Image Preview</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-8 text-center">
                    <img id="modalImageEl" src="" alt="" class="rounded-3"
                        style="max-width: 100%; max-height: 75vh;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVideo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h2 class="fw-bold mb-0" id="modalVideoTitle">Video Player</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-8">
                    <div id="modalVideoWrap" class="ratio ratio-16x9 rounded-3 overflow-hidden"></div>
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
            let pages = @json($pages);
            const hotspots = @json($hotspots);
            const settings = @json($viewerSettings);
            const trackUrl = @json(route('catalog.pdfs.analytics.track', $pdf));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
            }

            const flipbookEl = document.getElementById('flipbook');
            const pageInfoEl = document.getElementById('pageInfo');
            const loadingMessageEl = document.getElementById('loadingMessage');
            const bookFrameEl = document.querySelector('.share-book-frame');
            const modalProductEl = document.getElementById('modalProduct');
            const modalProductTitleEl = document.getElementById('modalProductTitle');
            const modalProductNameEl = document.getElementById('modalProductName');
            const modalProductDescEl = document.getElementById('modalProductDesc');
            const modalProductPriceEl = document.getElementById('modalProductPrice');
            const modalProductPriceRowEl = document.getElementById('modalProductPriceRow');
            const modalProductThumbWrapperEl = document.getElementById('modalProductThumbWrapper');
            const modalProductInfoColumnEl = document.getElementById('modalProductInfoColumn');
            const modalProductThumbEl = document.getElementById('modalProductThumb');
            const modalProductLinkEl = document.getElementById('modalProductLink');
            const mediaBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/hotspots'));
            const pageImageBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages'));

            let $flipbook;
            let dragStartX = null;
            let dragPointerId = null;

            const hotspotByPageId = {};
            for (const hotspot of hotspots) {
                const pageId = String(hotspot.catalog_pdf_page_id);
                hotspotByPageId[pageId] = hotspotByPageId[pageId] || [];
                hotspotByPageId[pageId].push(hotspot);
            }

            modalProductEl?.addEventListener('hidden.bs.modal', resetProductModal);

            function resetProductModal() {
                modalProductTitleEl.textContent = 'Product Details';
                modalProductNameEl.textContent = '';
                modalProductDescEl.textContent = 'Product details will appear here when available.';
                modalProductPriceEl.textContent = '';
                modalProductPriceRowEl.classList.add('d-none');
                modalProductLinkEl.href = '#';
                modalProductLinkEl.classList.add('d-none');
                modalProductThumbEl.removeAttribute('src');
                modalProductThumbEl.alt = '';
                modalProductThumbWrapperEl.classList.add('d-none');
                modalProductInfoColumnEl.classList.remove('col-lg-8');
                modalProductInfoColumnEl.classList.add('col-lg-12');
            }

            function sendAnalytics(eventType, payload = {}, keepalive = false) {
                try {
                    fetch(trackUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            event_type: eventType,
                            ...payload,
                        }),
                        keepalive,
                    }).catch(() => {});
                } catch (error) {}
            }

            function updatePageInfo() {
                if (!pageInfoEl) {
                    return;
                }

                if (!$flipbook) {
                    pageInfoEl.textContent = 'Page 1';
                    return;
                }

                const current = $flipbook.turn('page');
                const total = $flipbook.turn('pages');
                pageInfoEl.textContent = `Page ${current} of ${total}`;
            }

            function mediaUrl(id, kind) {
                return mediaBase + '/' + id + '/media/' + kind;
            }

            function resolveCssColor(value) {
                const trimmed = String(value ?? '').trim();
                if (!trimmed || !window.CSS?.supports?.('color', trimmed)) {
                    return '';
                }

                const probe = document.createElement('span');
                probe.style.display = 'none';
                probe.style.color = trimmed;
                document.body.appendChild(probe);
                const resolved = window.getComputedStyle(probe).color || '';
                probe.remove();

                return resolved;
            }

            function colorWithAlpha(value, alpha) {
                const resolved = resolveCssColor(value);
                const match = resolved.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*[\d.]+)?\)$/i);

                if (!match) {
                    return '';
                }

                return `rgba(${match[1]}, ${match[2]}, ${match[3]}, ${alpha})`;
            }

            function colorWithFallback(value, alpha, fallback) {
                return colorWithAlpha(value, alpha) || fallback;
            }

            function normalizePagesForRendering(pdf) {
                const totalPages = pdf.numPages || 0;

                if (!Array.isArray(pages) || pages.length === 0) {
                    pages = [];
                    for (let index = 1; index <= totalPages; index++) {
                        pages.push({
                            id: null,
                            page_number: index,
                            render_page_number: index,
                            display_order: index,
                            title: 'Page ' + index,
                            image_path: null,
                        });
                    }

                    return;
                }

                pages = pages
                    .map((page, index) => ({
                        ...page,
                        render_page_number: index + 1,
                    }))
                    .filter((page) => page.render_page_number <= totalPages);
            }

            function pageIndexForPageNumber(pageNumber) {
                const index = pages.findIndex((page) => Number(page.page_number) === Number(pageNumber));
                return index >= 0 ? index + 1 : null;
            }

            function showModal(modalId) {
                const element = document.getElementById(modalId);
                if (!element || !window.bootstrap) {
                    return;
                }

                new window.bootstrap.Modal(element).show();
            }

            function handleAction(hotspot) {
                sendAnalytics('hotspot_click', {
                    page_number: hotspot.internal_page_number || pages[0]?.page_number || 1,
                    hotspot_id: hotspot.id,
                });

                if (hotspot.action_type === 'internal_page') {
                    const targetIndex = pageIndexForPageNumber(hotspot.internal_page_number);
                    if (targetIndex && $flipbook) {
                        $flipbook.turn('page', targetIndex);
                    }
                    return;
                }

                if (hotspot.action_type === 'external_link') {
                    if (hotspot.link) {
                        window.open(hotspot.link, '_blank');
                    }
                    return;
                }

                if (hotspot.action_type === 'popup_window') {
                    resetProductModal();

                    const title = String(hotspot.title || '').trim();
                    const description = String(hotspot.description || '').trim();
                    const price = String(hotspot.price || '').trim();
                    const link = String(hotspot.link || '').trim();

                    modalProductTitleEl.textContent = title || 'Product Details';
                    modalProductNameEl.textContent = title || 'Catalog Product';
                    modalProductDescEl.textContent = description || 'Product details will appear here when available.';

                    if (price) {
                        modalProductPriceEl.textContent = price;
                        modalProductPriceRowEl.classList.remove('d-none');
                    }

                    if (hotspot.thumbnail_path) {
                        modalProductThumbEl.src = mediaUrl(hotspot.id, 'thumbnail');
                        modalProductThumbEl.alt = title || 'Product thumbnail';
                        modalProductThumbWrapperEl.classList.remove('d-none');
                        modalProductInfoColumnEl.classList.remove('col-lg-12');
                        modalProductInfoColumnEl.classList.add('col-lg-8');
                    }

                    if (link) {
                        modalProductLinkEl.href = link;
                        modalProductLinkEl.classList.remove('d-none');
                    }

                    showModal('modalProduct');
                    return;
                }

                if (hotspot.action_type === 'popup_image') {
                    document.getElementById('modalImageTitle').textContent = hotspot.title || 'Image Preview';
                    document.getElementById('modalImageEl').src = mediaUrl(hotspot.id, 'popup_image');
                    showModal('modalImage');
                    return;
                }

                if (hotspot.action_type === 'popup_video') {
                    document.getElementById('modalVideoTitle').textContent = hotspot.title || 'Video Player';
                    const wrap = document.getElementById('modalVideoWrap');
                    wrap.innerHTML = '';

                    if (hotspot.popup_video_url) {
                        wrap.innerHTML =
                            `<iframe src="${hotspot.popup_video_url}" frameborder="0" allow="autoplay; fullscreen" allowfullscreen class="w-100 h-100"></iframe>`;
                    } else if (hotspot.popup_video_path) {
                        wrap.innerHTML =
                            `<video controls class="w-100 h-100"><source src="${mediaUrl(hotspot.id, 'popup_video')}" type="video/mp4">Your browser does not support video.</video>`;
                    }

                    showModal('modalVideo');
                }
            }

            function releaseDrag(inner) {
                dragStartX = null;
                dragPointerId = null;
                inner.classList.remove('is-dragging');
            }

            function bindDragFlip() {
                const dragThreshold = 80;
                const pageInners = flipbookEl.querySelectorAll('.page-inner');

                for (const inner of pageInners) {
                    inner.addEventListener('pointerdown', (event) => {
                        if (event.button !== 0 || event.target.closest('.hotspot')) {
                            return;
                        }

                        dragStartX = event.clientX;
                        dragPointerId = event.pointerId;
                        inner.classList.add('is-dragging');

                        if (typeof inner.setPointerCapture === 'function') {
                            inner.setPointerCapture(event.pointerId);
                        }
                    });

                    inner.addEventListener('pointerup', (event) => {
                        if (dragPointerId !== event.pointerId || dragStartX === null || event.target.closest(
                                '.hotspot')) {
                            return;
                        }

                        const deltaX = event.clientX - dragStartX;
                        if ($flipbook && Math.abs(deltaX) >= dragThreshold) {
                            $flipbook.turn(deltaX < 0 ? 'next' : 'previous');
                        }

                        if (typeof inner.releasePointerCapture === 'function' && inner.hasPointerCapture?.(event
                                .pointerId)) {
                            inner.releasePointerCapture(event.pointerId);
                        }

                        releaseDrag(inner);
                    });

                    inner.addEventListener('pointercancel', (event) => {
                        if (typeof inner.releasePointerCapture === 'function' && inner.hasPointerCapture?.(event
                                .pointerId)) {
                            inner.releasePointerCapture(event.pointerId);
                        }

                        releaseDrag(inner);
                    });
                }
            }

            function buildPageShells() {
                flipbookEl.innerHTML = '';

                for (const page of pages) {
                    const pageDiv = document.createElement('div');
                    pageDiv.className = 'page';
                    pageDiv.dataset.pageId = String(page.id || '');
                    pageDiv.dataset.pageNumber = String(page.page_number);

                    const inner = document.createElement('div');
                    inner.className = 'page-inner';

                    if (page.id && page.image_path) {
                        const img = document.createElement('img');
                        img.src = pageImageBase + '/' + page.id + '/image';
                        img.alt = page.title || ('Page ' + page.page_number);
                        inner.appendChild(img);
                    } else {
                        const canvas = document.createElement('canvas');
                        canvas.width = 10;
                        canvas.height = 10;
                        inner.appendChild(canvas);
                    }

                    const pageHotspots = hotspotByPageId[String(page.id)] || [];
                    for (const hotspot of pageHotspots) {
                        const hotspotEl = document.createElement('div');
                        hotspotEl.className = 'hotspot';
                        hotspotEl.style.left = (hotspot.x * 100) + '%';
                        hotspotEl.style.top = (hotspot.y * 100) + '%';
                        hotspotEl.style.width = (hotspot.w * 100) + '%';
                        hotspotEl.style.height = (hotspot.h * 100) + '%';
                        const trimmedColor = String(hotspot.color ?? '').trim();
                        if (trimmedColor && window.CSS?.supports?.('color', trimmedColor)) {
                            hotspotEl.classList.add('has-color');
                        }
                        hotspotEl.style.setProperty('--hotspot-surface-top', colorWithFallback(hotspot.color,
                            0.10, 'rgba(255, 255, 255, 0.10)'));
                        hotspotEl.style.setProperty('--hotspot-surface-bottom', colorWithFallback(hotspot.color,
                            0.08, 'rgba(226, 232, 240, 0.08)'));
                        hotspotEl.style.setProperty('--hotspot-surface-top-hover', colorWithFallback(hotspot.color,
                            0.18, 'rgba(255, 255, 255, 0.18)'));
                        hotspotEl.style.setProperty('--hotspot-surface-bottom-hover', colorWithFallback(hotspot.color,
                            0.12, 'rgba(226, 232, 240, 0.14)'));
                        hotspotEl.style.setProperty('--hotspot-edge', colorWithFallback(hotspot.color, 0.18,
                            'rgba(148, 163, 184, 0.22)'));
                        hotspotEl.style.setProperty('--hotspot-shadow', colorWithFallback(hotspot.color, 0.18,
                            'rgba(148, 163, 184, 0.28)'));
                        hotspotEl.style.setProperty('--hotspot-shadow-hover', colorWithFallback(hotspot.color,
                            0.24, 'rgba(148, 163, 184, 0.34)'));
                        hotspotEl.title = hotspot.title || hotspot.action_type;
                        hotspotEl.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            handleAction(hotspot);
                        });
                        inner.appendChild(hotspotEl);
                    }

                    pageDiv.appendChild(inner);
                    flipbookEl.appendChild(pageDiv);
                }

                bindDragFlip();
            }

            function computeTurnSize(viewport) {
                const frameRect = bookFrameEl?.getBoundingClientRect();
                const frameWidth = Math.max((frameRect?.width || window.innerWidth) - 56, 280);
                const frameHeight = Math.max((frameRect?.height || window.innerHeight) - 56, 280);
                const isMobile = window.innerWidth < 992;
                const display = settings.displayMode === 'auto' ?
                    (isMobile ? 'single' : 'double') :
                    settings.displayMode;
                const visibleSpreadWidth = display === 'double' ? frameWidth / 2 : frameWidth;
                const scaleWidth = visibleSpreadWidth / viewport.width;
                const scaleHeight = frameHeight / viewport.height;
                const baseScale = Math.min(scaleWidth, scaleHeight);
                const preferredScale = baseScale * Math.max(Number(settings.renderScale) || 1, 1);
                const scale = Math.min(preferredScale, baseScale);

                return {
                    w: Math.floor(viewport.width * scale),
                    h: Math.floor(viewport.height * scale),
                    display,
                    scale,
                };
            }

            async function renderMissingCanvases(pdf, sizing) {
                const pageDivs = flipbookEl.querySelectorAll('.page');

                for (let index = 0; index < pages.length; index++) {
                    const page = pages[index];
                    if (page.id && page.image_path) {
                        continue;
                    }

                    const pageDiv = pageDivs[index];
                    const canvas = pageDiv.querySelector('canvas');
                    if (!canvas) {
                        continue;
                    }

                    const pdfPage = await pdf.getPage(page.render_page_number || (index + 1));
                    const viewport = pdfPage.getViewport({
                        scale: sizing.scale
                    });
                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);

                    await pdfPage.render({
                        canvasContext: canvas.getContext('2d'),
                        viewport,
                    }).promise;
                }
            }

            async function render() {
                if (!window.pdfjsLib || !window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !==
                    'function') {
                    loadingMessageEl.textContent = 'Required viewer libraries failed to load.';
                    return;
                }

                let pdf;
                try {
                    pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                } catch (error) {
                    console.error(error);
                    loadingMessageEl.textContent = 'Failed to load PDF.';
                    return;
                }

                normalizePagesForRendering(pdf);

                if (pages.length === 0) {
                    loadingMessageEl.textContent = 'No pages available to share.';
                    return;
                }

                buildPageShells();

                const firstPage = await pdf.getPage(pages[0].render_page_number || 1);
                const rawViewport = firstPage.getViewport({
                    scale: 1
                });
                const sizing = computeTurnSize(rawViewport);

                const pageDivs = flipbookEl.querySelectorAll('.page');
                for (const pageDiv of pageDivs) {
                    pageDiv.style.width = sizing.w + 'px';
                    pageDiv.style.height = sizing.h + 'px';
                }

                await renderMissingCanvases(pdf, sizing);
                loadingMessageEl.style.display = 'none';

                $flipbook = $('#flipbook');
                flipbookEl.style.width = (sizing.display === 'double' ? sizing.w * 2 : sizing.w) + 'px';
                flipbookEl.style.height = sizing.h + 'px';
                $flipbook.turn({
                    width: sizing.display === 'double' ? sizing.w * 2 : sizing.w,
                    height: sizing.h,
                    autoCenter: true,
                    display: sizing.display,
                    duration: Math.max(Number(settings.duration) || 900, 1100),
                    acceleration: settings.acceleration,
                    gradients: settings.gradients,
                    elevation: Math.max(Number(settings.elevation) || 50, 70),
                });

                $flipbook.bind('turned', function() {
                    updatePageInfo();
                    const currentPage = $flipbook.turn('page');
                    sendAnalytics('page_view', {
                        page_number: Number(pages[currentPage - 1]?.page_number || 1),
                        meta: {
                            source: 'turn',
                        },
                    });
                });

                sendAnalytics('book_open', {
                    page_number: Number(pages[0]?.page_number || 1),
                    meta: {
                        source: 'share',
                    },
                });

                updatePageInfo();
                document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));

                window.addEventListener('keydown', (event) => {
                    if (!$flipbook) {
                        return;
                    }

                    if (event.key === 'ArrowLeft') {
                        $flipbook.turn('previous');
                    }

                    if (event.key === 'ArrowRight') {
                        $flipbook.turn('next');
                    }
                });
            }

            // Initialize the PDF rendering process
            render();
        })();
    </script>
</body>

</html>
