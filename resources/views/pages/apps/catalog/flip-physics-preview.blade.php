<x-default-layout>

    @section('title')
        Flip Physics Preview - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.flip-physics.preview', $pdf) }}
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
            <a href="{{ route('catalog.pdfs.flip-physics.edit', $pdf) }}" class="btn btn-light btn-active-light-primary">
                Back to Settings
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="flipbook-toolbar mb-5">
                <div class="d-flex gap-2">
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

            <div class="text-muted mt-5">
                Preview with {{ $setting->preset }} physics settings.
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Flip Physics Flipbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Share this link to allow others to view the flipbook:</p>
                    <div class="share-link-container">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.flip-physics.share', $pdf) }}">
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

                $settings = [
                    'duration' => $setting - > duration_ms,
                    'gradients' => $setting - > gradients,
                    'acceleration' => $setting - > acceleration,
                    'elevation' => $setting - > elevation,
                    'displayMode' => $setting - > display_mode,
                    'renderScale' => $setting - > render_scale_percent / 100,
                ];

                const statusEl = document.getElementById('status');
                const flipbookEl = document.getElementById('flipbook');
                const pageInfoEl = document.getElementById('pageInfo');

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

                function destroyTurnIfExists() {
                    try {
                        const $fb = $('#flipbook');
                        if ($fb.data('turn')) {
                            $fb.turn('destroy');
                        }
                    } catch (e) {}
                }

                async function renderAll() {
                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        setStatus('Turn.js failed to load.');
                        return;
                    }

                    setStatus('Loading PDF…');

                    let pdf;
                    try {
                        pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    } catch (e) {
                        console.error(e);
                        setStatus('Failed to load PDF.');
                        return;
                    }

                    const pageCount = pdf.numPages || 0;

                    if (pageCount <= 0) {
                        setStatus('No pages found in PDF.');
                        return;
                    }

                    destroyTurnIfExists();
                    buildPageShells(pageCount);

                    // Size based on first page
                    const first = await pdf.getPage(1);
                    const rawViewport = first.getViewport({
                        scale: 1
                    });

                    const containerWidth = Math.min(1200, document.getElementById('kt_app_content_container')
                        ?.clientWidth || window.innerWidth);
                    const targetSingleWidth = Math.min(520, Math.max(320, containerWidth * 0.45));

                    const scaleToFit = (targetSingleWidth / rawViewport.width) * settings.renderScale;
                    const w = Math.floor(rawViewport.width * scaleToFit);
                    const h = Math.floor(rawViewport.height * scaleToFit);

                    const isMobile = window.innerWidth < 992;
                    const display = (settings.displayMode === 'auto') ? (isMobile ? 'single' : 'double') : settings
                        .displayMode;

                    const pageDivs = flipbookEl.querySelectorAll('.page');

                    for (let i = 1; i <= pageCount; i++) {
                        setStatus(`Rendering page ${i} of ${pageCount}…`);
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({
                            scale: scaleToFit
                        });

                        const pageDiv = pageDivs[i - 1];
                        pageDiv.style.width = w + 'px';
                        pageDiv.style.height = h + 'px';

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

                    $flipbook = $('#flipbook');
                    flipbookEl.style.width = (display === 'double' ? w * 2 : w) + 'px';
                    flipbookEl.style.height = h + 'px';
                    $flipbook.turn({
                        width: display === 'double' ? w * 2 : w,
                        height: h,
                        autoCenter: true,
                        display: display,
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

                    document.getElementById('btnPrev').onclick = () => $flipbook.turn('previous');
                    document.getElementById('btnNext').onclick = () => $flipbook.turn('next');

                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowLeft') $flipbook.turn('previous');
                        if (e.key === 'ArrowRight') $flipbook.turn('next');
                        if (e.key === 'Escape' && isFullscreen) exitFullscreen();
                    });
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

                // Initial render
                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
