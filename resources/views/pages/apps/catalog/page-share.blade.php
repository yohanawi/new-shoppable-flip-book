<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pdf->title }} - Shared Flipbook</title>
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

        #flipbook canvas {
            width: 100%;
            height: 100%;
            display: block;
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
    </style>
</head>

<body>
    <div class="share-container">
        <div class="share-toolbar">
            <div class="d-flex align-items-center">
                <h1 class="share-title">{{ $pdf->title }}</h1>
                <span class="page-info" id="pageInfo"></span>
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
            <div class="loading-message" id="loadingMessage">Loading flipbook...</div>
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
            const trackUrl = @json(route('catalog.pdfs.analytics.track', $pdf));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const flipbookEl = document.getElementById('flipbook');
            const pageInfoEl = document.getElementById('pageInfo');
            const loadingMessageEl = document.getElementById('loadingMessage');

            const hasDbPages = Array.isArray(pages) && pages.length > 0;
            let $flipbook;

            function createAnalyticsTracker(resolvePageNumber) {
                let isStarted = false;
                let activeSince = null;
                let heartbeatId = null;
                let isDestroyed = false;

                function send(eventType, payload = {}, keepalive = false) {
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

                function flushReadingTime(reason) {
                    if (activeSince === null) {
                        return;
                    }

                    const durationMs = Math.max(0, Math.min(Date.now() - activeSince, 600000));
                    activeSince = document.visibilityState === 'visible' ? Date.now() : null;

                    if (durationMs < 1000) {
                        return;
                    }

                    send('reading_time', {
                        page_number: resolvePageNumber(),
                        meta: {
                            duration_ms: durationMs,
                            reason,
                        },
                    }, true);
                }

                function start() {
                    if (isStarted) {
                        return;
                    }

                    isStarted = true;
                    activeSince = document.visibilityState === 'visible' ? Date.now() : null;

                    send('book_open', {
                        page_number: resolvePageNumber(),
                        meta: {
                            source: 'share',
                        },
                    });

                    send('page_view', {
                        page_number: resolvePageNumber(),
                        meta: {
                            source: 'initial',
                        },
                    });

                    heartbeatId = window.setInterval(() => flushReadingTime('heartbeat'), 15000);
                }

                function pageView(source = 'turn') {
                    if (!isStarted) {
                        return;
                    }

                    flushReadingTime('page_change');
                    activeSince = document.visibilityState === 'visible' ? Date.now() : null;

                    send('page_view', {
                        page_number: resolvePageNumber(),
                        meta: {
                            source,
                        },
                    });
                }

                function destroy() {
                    if (!isStarted || isDestroyed) {
                        return;
                    }

                    isDestroyed = true;
                    flushReadingTime('unload');

                    if (heartbeatId !== null) {
                        window.clearInterval(heartbeatId);
                    }
                }

                document.addEventListener('visibilitychange', () => {
                    if (!isStarted) {
                        return;
                    }

                    if (document.visibilityState === 'hidden') {
                        flushReadingTime('hidden');
                        return;
                    }

                    activeSince = Date.now();
                });

                window.addEventListener('pagehide', destroy);
                window.addEventListener('beforeunload', destroy);

                return {
                    start,
                    pageView,
                };
            }

            const analytics = createAnalyticsTracker(() => {
                if (!$flipbook || !pages || pages.length === 0) {
                    return Number(pages?.[0]?.page_number || 1);
                }

                const turnPage = $flipbook.turn('page');
                return Number(pages[turnPage - 1]?.page_number || pages[0]?.page_number || 1);
            });

            // Configure PDF.js worker
            if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
            }

            function updatePageInfo() {
                if (!$flipbook || !pages || pages.length === 0) return;

                const currentPage = $flipbook.turn('page');
                const totalPages = $flipbook.turn('pages');
                pageInfoEl.textContent = `Page ${currentPage} of ${totalPages}`;
            }

            function buildPageShells() {
                flipbookEl.innerHTML = '';
                for (const p of pages) {
                    const pageDiv = document.createElement('div');
                    pageDiv.className = 'page';
                    pageDiv.dataset.pageNumber = String(p.page_number);

                    const canvas = document.createElement('canvas');
                    canvas.width = 10;
                    canvas.height = 10;
                    pageDiv.appendChild(canvas);

                    flipbookEl.appendChild(pageDiv);
                }
            }

            function computeTurnSize(pageViewport) {
                const containerWidth = Math.min(window.innerWidth - 40, 1400);
                const containerHeight = window.innerHeight - 120;

                // Calculate scale based on available space
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

            async function render() {
                let pdf;
                try {
                    pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                } catch (e) {
                    console.error(e);
                    loadingMessageEl.textContent = 'Failed to load PDF.';
                    return;
                }

                // If no pages were initialized in DB yet, generate a default list from the PDF
                if (!hasDbPages) {
                    const n = pdf.numPages || 0;
                    pages = [];
                    for (let i = 1; i <= n; i++) {
                        pages.push({
                            page_number: i,
                            title: '',
                            display_order: i
                        });
                    }
                }

                if (!pages || pages.length === 0) {
                    loadingMessageEl.textContent = 'No pages to preview.';
                    return;
                }

                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                    loadingMessageEl.textContent = 'Turn.js failed to load.';
                    return;
                }

                buildPageShells();

                // Use first visible page to compute sizing
                const firstPageNo = pages[0].page_number;
                const firstPage = await pdf.getPage(firstPageNo);
                const rawViewport = firstPage.getViewport({
                    scale: 1
                });
                const sizing = computeTurnSize(rawViewport);

                const pageDivs = flipbookEl.querySelectorAll('.page');
                for (let i = 0; i < pages.length; i++) {
                    const p = pages[i];
                    loadingMessageEl.textContent = `Rendering page ${i + 1} of ${pages.length}...`;

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

                loadingMessageEl.style.display = 'none';

                // Initialize turn.js after canvases exist
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

                    // Update page info on turn
                    $flipbook.bind('turned', function(event, page) {
                        updatePageInfo();
                        analytics.pageView('turn');
                    });

                    updatePageInfo();
                    analytics.start();

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
