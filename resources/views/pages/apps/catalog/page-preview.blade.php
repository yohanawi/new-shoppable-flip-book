<x-default-layout>

    @section('title')
        Page Management - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.page-management', $pdf) }}
    @endsection

    <style>
        #flipbook {
            margin: 0 auto;
        }

        #flipbook .page {
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        #flipbook canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .flipbook-toolbar {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .flipbook-toolbar .btn {
            white-space: nowrap;
        }

        .flipbook-status {
            color: #7e8299;
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

        /* Share modal */
        .share-link-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .share-link-container input {
            flex: 1;
        }
    </style>

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.manage', $pdf) }}" class="btn btn-light btn-active-light-primary">Back to Page
                Management</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="flipbook-toolbar mb-5">
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-light" id="btnPrev">Previous</button>
                    <button type="button" class="btn btn-light" id="btnNext">Next</button>
                    <span class="flipbook-status" id="pageInfo"></span>
                </div>

                <div class="flipbook-status" id="status">Rendering pages…</div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light-info" id="btnFullscreen">
                        <i class="bi bi-fullscreen"></i> Fullscreen
                    </button>
                    <button type="button" class="btn btn-light-success" id="btnShare">
                        <i class="bi bi-share"></i> Share
                    </button>
                    <a class="btn btn-light-primary" href="{{ route('catalog.pdfs.download', $pdf) }}">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-center">
                <div id="flipbook"></div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Flipbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Share this link to allow others to view the flipbook:</p>
                    <div class="share-link-container">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.share', $pdf) }}">
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

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                let pages = @json($pages);

                const statusEl = document.getElementById('status');
                const flipbookEl = document.getElementById('flipbook');
                const pageInfoEl = document.getElementById('pageInfo');

                const hasDbPages = Array.isArray(pages) && pages.length > 0;
                let isFullscreen = false;
                let $flipbook;

                // Configure PDF.js worker
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function setStatus(text) {
                    statusEl.textContent = text;
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

                        // Optional title overlay
                        if (p.title) {
                            const label = document.createElement('div');
                            label.style.position = 'absolute';
                            label.style.left = '10px';
                            label.style.bottom = '10px';
                            label.style.padding = '4px 8px';
                            label.style.background = 'rgba(0,0,0,0.55)';
                            label.style.color = '#fff';
                            label.style.borderRadius = '6px';
                            label.style.fontSize = '12px';
                            label.style.zIndex = '2';
                            label.textContent = p.title;
                            pageDiv.style.position = 'relative';
                            pageDiv.appendChild(label);
                        }

                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function computeTurnSize(pageViewport) {
                    const containerWidth = Math.min(1200, document.getElementById('kt_app_content_container')
                        ?.clientWidth || window.innerWidth);

                    // render a single PDF page around 520px wide on desktop
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

                async function render() {
                    setStatus('Loading PDF…');

                    let pdf;
                    try {
                        pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    } catch (e) {
                        console.error(e);
                        setStatus('Failed to load PDF. Open the source URL to verify it returns a PDF: ' + pdfUrl);
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
                        setStatus('No pages to preview.');
                        return;
                    }

                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        setStatus('Turn.js failed to load — pages will show as a list.');
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

                    setStatus('Ready');

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
                        });

                        updatePageInfo();

                        document.getElementById('btnPrev').addEventListener('click', () => $flipbook.turn('previous'));
                        document.getElementById('btnNext').addEventListener('click', () => $flipbook.turn('next'));

                        window.addEventListener('keydown', (e) => {
                            if (e.key === 'ArrowLeft') $flipbook.turn('previous');
                            if (e.key === 'ArrowRight') $flipbook.turn('next');
                            if (e.key === 'Escape' && isFullscreen) exitFullscreen();
                        });
                    } catch (e) {
                        console.error(e);
                        setStatus('Rendered, but flip effect failed to initialize.');
                    }
                }

                // Fullscreen functionality
                function enterFullscreen() {
                    const container = document.querySelector('.card');
                    const toolbar = document.querySelector('.flipbook-toolbar');

                    isFullscreen = true;
                    container.classList.add('fullscreen-mode');

                    // Create fullscreen toolbar
                    const fsToolbar = document.createElement('div');
                    fsToolbar.className = 'fullscreen-toolbar';
                    fsToolbar.innerHTML = `
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-light" id="btnPrevFs">Previous</button>
                            <button type="button" class="btn btn-sm btn-light" id="btnNextFs">Next</button>
                            <span id="pageInfoFs" style="margin-left: 15px;"></span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-light" id="btnExitFullscreen">
                                <i class="bi bi-fullscreen-exit"></i> Exit Fullscreen
                            </button>
                        </div>
                    `;

                    container.insertBefore(fsToolbar, container.firstChild);

                    // Hide original toolbar
                    toolbar.style.display = 'none';

                    // Add event listeners
                    document.getElementById('btnPrevFs').addEventListener('click', () => $flipbook.turn('previous'));
                    document.getElementById('btnNextFs').addEventListener('click', () => $flipbook.turn('next'));
                    document.getElementById('btnExitFullscreen').addEventListener('click', exitFullscreen);

                    // Update page info in fullscreen toolbar
                    $flipbook.bind('turned.fullscreen', function() {
                        const currentPage = $flipbook.turn('page');
                        const totalPages = $flipbook.turn('pages');
                        document.getElementById('pageInfoFs').textContent = `Page ${currentPage} of ${totalPages}`;
                    });

                    // Trigger initial update
                    const currentPage = $flipbook.turn('page');
                    const totalPages = $flipbook.turn('pages');
                    document.getElementById('pageInfoFs').textContent = `Page ${currentPage} of ${totalPages}`;
                }

                function exitFullscreen() {
                    const container = document.querySelector('.card');
                    const toolbar = document.querySelector('.flipbook-toolbar');
                    const fsToolbar = container.querySelector('.fullscreen-toolbar');

                    isFullscreen = false;
                    container.classList.remove('fullscreen-mode');

                    if (fsToolbar) {
                        fsToolbar.remove();
                    }

                    toolbar.style.display = 'flex';

                    // Unbind fullscreen events
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
                    linkInput.setSelectionRange(0, 99999); // For mobile

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
