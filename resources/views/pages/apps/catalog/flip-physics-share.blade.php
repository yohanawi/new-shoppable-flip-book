<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pdf->title }} - Flip Physics Flipbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #1e1e2d;
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
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        .share-title {
            font-size: 18px;
            font-weight: 600;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
        }

        .loading-message {
            color: #fff;
            text-align: center;
            font-size: 16px;
        }

        .settings-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-left: 15px;
        }
    </style>
</head>

<body>
    <div class="share-container">
        <div class="share-toolbar">
            <div class="d-flex align-items-center">
                <h1 class="share-title">{{ $pdf->title }}</h1>
                <span class="settings-badge">{{ ucfirst($setting->preset) }} Physics</span>
                <span class="page-info" id="pageInfo"></span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light" id="btnPrev">
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button type="button" class="btn btn-sm btn-light" id="btnNext">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
                <a class="btn btn-sm btn-primary" href="{{ route('catalog.pdfs.download', $pdf) }}">
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
            $settings = [
                'duration' => $setting - > duration_ms,
                'gradients' => $setting - > gradients,
                'acceleration' => $setting - > acceleration,
                'elevation' => $setting - > elevation,
                'displayMode' => $setting - > display_mode,
                'renderScale' => $setting - > render_scale_percent / 100,
            ];

            const flipbookEl = document.getElementById('flipbook');
            const pageInfoEl = document.getElementById('pageInfo');
            const loadingMessageEl = document.getElementById('loadingMessage');

            let $flipbook;

            // Configure PDF.js worker
            if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
            }

            function updatePageInfo() {
                if (!$flipbook) return;

                const currentPage = $flipbook.turn('page');
                const totalPages = $flipbook.turn('pages');
                pageInfoEl.textContent = `Page ${currentPage} of ${totalPages}`;
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

            function computeTurnSize(pageViewport) {
                const containerWidth = Math.min(window.innerWidth - 40, 1400);
                const containerHeight = window.innerHeight - 120;

                // Calculate scale based on available space
                const scaleWidth = (containerWidth * 0.45) / pageViewport.width;
                const scaleHeight = containerHeight / pageViewport.height;
                const scale = Math.min(scaleWidth, scaleHeight) * settings.renderScale;

                const w = Math.floor(pageViewport.width * scale);
                const h = Math.floor(pageViewport.height * scale);

                const isMobile = window.innerWidth < 992;
                const display = (settings.displayMode === 'auto') ? (isMobile ? 'single' : 'double') : settings
                    .displayMode;

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

                const pageCount = pdf.numPages || 0;

                if (pageCount <= 0) {
                    loadingMessageEl.textContent = 'No pages to preview.';
                    return;
                }

                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                    loadingMessageEl.textContent = 'Turn.js failed to load.';
                    return;
                }

                buildPageShells(pageCount);

                // Use first page to compute sizing
                const firstPage = await pdf.getPage(1);
                const rawViewport = firstPage.getViewport({
                    scale: 1
                });
                const sizing = computeTurnSize(rawViewport);

                const pageDivs = flipbookEl.querySelectorAll('.page');
                for (let i = 1; i <= pageCount; i++) {
                    loadingMessageEl.textContent = `Rendering page ${i} of ${pageCount}...`;

                    const page = await pdf.getPage(i);
                    const viewport = page.getViewport({
                        scale: sizing.scale
                    });

                    const pageDiv = pageDivs[i - 1];
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

                // Initialize turn.js with saved settings
                try {
                    $flipbook = $('#flipbook');
                    flipbookEl.style.width = (sizing.display === 'double' ? sizing.w * 2 : sizing.w) + 'px';
                    flipbookEl.style.height = sizing.h + 'px';
                    $flipbook.turn({
                        width: sizing.display === 'double' ? sizing.w * 2 : sizing.w,
                        height: sizing.h,
                        autoCenter: true,
                        display: sizing.display,
                        duration: settings.duration,
                        acceleration: settings.acceleration,
                        gradients: settings.gradients,
                        elevation: settings.elevation,
                    });

                    // Update page info on turn
                    $flipbook.bind('turned', function(event, page) {
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
