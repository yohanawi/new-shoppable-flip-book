<x-default-layout>

    @section('title')
        Flip Physics Preview - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.flip-physics.preview', $pdf) }}
    @endsection

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.flip-physics.edit', $pdf) }}" class="btn btn-dark btn-active-light-primary">
                <i class="ki-outline ki-arrow-left fs-2"></i> Back to Settings
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0" id="previewCard">
        <div class="card-body d-flex flex-column gap-5" id="previewCardBody">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-light" id="btnPrev">Previous</button>
                    <button type="button" class="btn btn-light" id="btnNext">Next</button>
                    <span class="text-muted fs-7 fw-semibold" id="pageInfo"></span>
                </div>

                <div class="text-muted fs-7 fw-semibold" id="status">Rendering pages…</div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-light-info" id="btnFullscreen">
                        <i class="bi bi-fullscreen"></i> Fullscreen
                    </button>

                </div>
            </div>

            <div id="flipbookStage"
                class="d-flex justify-content-center align-items-center bg-light-primary rounded-4 border border-dashed border-primary p-4 p-xl-6 overflow-auto flex-grow-1">
                <div id="flipbook" class="mx-auto"></div>
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
                    <div class="input-group mt-4">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.flip-physics.share', $pdf) }}">
                        <button type="button" class="btn btn-primary" id="btnCopyLink">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="{{ route('catalog.pdfs.share-preview.edit', $pdf) }}" class="btn btn-light-info">
                            <i class="bi bi-layout-text-window-reverse me-2"></i> Share Preview Studio
                        </a>
                    </div>
                    <div class="mt-3 d-none" id="copySuccess">
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
                const settings = @json($viewerSettings);

                const statusEl = document.getElementById('status');
                let flipbookEl = document.getElementById('flipbook');
                const pageInfoEl = document.getElementById('pageInfo');
                const previewCardEl = document.getElementById('previewCard');
                const previewCardBodyEl = document.getElementById('previewCardBody');
                const flipbookStageEl = document.getElementById('flipbookStage');
                const fullscreenButtonEl = document.getElementById('btnFullscreen');
                const previousButtonEl = document.getElementById('btnPrev');
                const nextButtonEl = document.getElementById('btnNext');
                const shareButtonEl = document.getElementById('btnShare');
                const copyLinkButtonEl = document.getElementById('btnCopyLink');
                const shareModalEl = document.getElementById('shareModal');
                const shareLinkEl = document.getElementById('shareLink');
                const copySuccessEl = document.getElementById('copySuccess');

                let isFullscreen = false;
                let $flipbook = null;
                let keyboardBound = false;
                let resizeTimer = null;
                let pdfDocumentPromise = null;
                let pdfDocument = null;
                let renderGeneration = 0;

                // Configure PDF.js worker
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function setStatus(text) {
                    statusEl.textContent = text;
                }

                function setNavigationEnabled(enabled) {
                    previousButtonEl.disabled = !enabled;
                    nextButtonEl.disabled = !enabled;
                }

                function hasActiveFlipbook() {
                    if (!$flipbook || typeof $flipbook.turn !== 'function') {
                        return false;
                    }

                    try {
                        return Number($flipbook.turn('pages')) > 0;
                    } catch (error) {
                        return false;
                    }
                }

                async function getPdfDocument() {
                    if (pdfDocument) {
                        return pdfDocument;
                    }

                    if (!pdfDocumentPromise) {
                        pdfDocumentPromise = window.pdfjsLib.getDocument(pdfUrl).promise.then(function(document) {
                            pdfDocument = document;
                            return document;
                        });
                    }

                    return pdfDocumentPromise;
                }

                function updatePageInfo() {
                    if (!hasActiveFlipbook()) {
                        pageInfoEl.textContent = '';
                        return;
                    }

                    const currentPage = $flipbook.turn('page');
                    const totalPages = $flipbook.turn('pages');
                    pageInfoEl.textContent = `Page ${currentPage} of ${totalPages}`;
                }

                function syncFullscreenState() {
                    isFullscreen = document.fullscreenElement === previewCardEl;

                    previewCardEl.classList.toggle('rounded-0', isFullscreen);
                    previewCardEl.classList.toggle('bg-dark', isFullscreen);
                    previewCardEl.classList.toggle('shadow-none', isFullscreen);
                    previewCardBodyEl.classList.toggle('h-100', isFullscreen);

                    flipbookStageEl.classList.toggle('bg-light-primary', !isFullscreen);
                    flipbookStageEl.classList.toggle('border', !isFullscreen);
                    flipbookStageEl.classList.toggle('border-dashed', !isFullscreen);
                    flipbookStageEl.classList.toggle('border-primary', !isFullscreen);
                    flipbookStageEl.classList.toggle('rounded-4', !isFullscreen);
                    flipbookStageEl.classList.toggle('bg-dark', isFullscreen);
                    flipbookStageEl.classList.toggle('rounded-0', isFullscreen);

                    statusEl.classList.toggle('text-white', isFullscreen);
                    pageInfoEl.classList.toggle('text-white', isFullscreen);

                    fullscreenButtonEl.innerHTML = isFullscreen ?
                        '<i class="bi bi-fullscreen-exit"></i> Exit Fullscreen' :
                        '<i class="bi bi-fullscreen"></i> Fullscreen';

                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 900;
                    const stageHeight = isFullscreen ? Math.max(viewportHeight - 132, 320) : Math.max(Math.min(
                        Math.floor(viewportHeight * 0.68), 920), 460);

                    previewCardEl.style.width = isFullscreen ? '100%' : '';
                    previewCardEl.style.height = isFullscreen ? `${viewportHeight}px` : '';
                    previewCardBodyEl.style.height = isFullscreen ? '100%' : '';
                    flipbookStageEl.style.height = `${stageHeight}px`;
                }

                function computeLayout(rawViewport) {
                    const stageStyles = window.getComputedStyle(flipbookStageEl);
                    const horizontalPadding = (parseFloat(stageStyles.paddingLeft) || 0) + (parseFloat(stageStyles
                        .paddingRight) || 0);
                    const verticalPadding = (parseFloat(stageStyles.paddingTop) || 0) + (parseFloat(stageStyles
                        .paddingBottom) || 0);

                    const stageWidth = Math.max(flipbookStageEl.clientWidth - horizontalPadding - 8, 260);
                    const stageHeight = Math.max(flipbookStageEl.clientHeight - verticalPadding - 8, 320);
                    const display = settings.displayMode === 'auto' ? (stageWidth < 900 || window.innerWidth < 992 ?
                        'single' : 'double') : settings.displayMode;
                    const singlePageWidth = display === 'double' ? stageWidth / 2 : stageWidth;
                    const displayScale = Math.max(Math.min(singlePageWidth / rawViewport.width, stageHeight /
                        rawViewport.height), 0.2);
                    const renderScale = Math.max(Number(settings.renderScale) || 1, 1);

                    const pageWidth = Math.max(Math.floor(rawViewport.width * displayScale), 160);
                    const pageHeight = Math.max(Math.floor(rawViewport.height * displayScale), 220);

                    return {
                        display,
                        pageWidth,
                        pageHeight,
                        bookWidth: display === 'double' ? pageWidth * 2 : pageWidth,
                        bookHeight: pageHeight,
                        canvasScale: displayScale * renderScale,
                    };
                }

                function buildPageShells(pageCount) {
                    flipbookEl.innerHTML = '';
                    for (let i = 1; i <= pageCount; i++) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page bg-white overflow-hidden shadow rounded-3';
                        pageDiv.dataset.pageNumber = String(i);

                        const canvas = document.createElement('canvas');
                        canvas.className = 'w-100 h-100 d-block';
                        canvas.width = 10;
                        canvas.height = 10;
                        pageDiv.appendChild(canvas);

                        flipbookEl.appendChild(pageDiv);
                    }
                }

                function resetFlipbookElement() {
                    if (!flipbookEl?.parentNode) {
                        return;
                    }

                    const nextFlipbookEl = document.createElement('div');
                    nextFlipbookEl.id = 'flipbook';
                    nextFlipbookEl.className = 'mx-auto';
                    flipbookEl.parentNode.replaceChild(nextFlipbookEl, flipbookEl);
                    flipbookEl = nextFlipbookEl;
                }

                function destroyTurnIfExists() {
                    try {
                        const $fb = $('#flipbook');
                        if (hasActiveFlipbook()) {
                            $fb.turn('stop');
                        }
                    } catch (e) {}

                    resetFlipbookElement();
                    $flipbook = null;
                    setNavigationEnabled(false);
                }

                async function renderAll() {
                    const currentRenderGeneration = ++renderGeneration;

                    if (!window.pdfjsLib) {
                        setStatus('PDF.js failed to load.');
                        return;
                    }

                    setStatus('Loading PDF…');
                    setNavigationEnabled(false);

                    let pdf;
                    try {
                        pdf = await getPdfDocument();
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

                    const currentPage = hasActiveFlipbook() ? $flipbook.turn('page') : 1;

                    destroyTurnIfExists();
                    buildPageShells(pageCount);

                    // Size based on first page
                    const first = await pdf.getPage(1);

                    if (currentRenderGeneration !== renderGeneration) {
                        return;
                    }

                    const rawViewport = first.getViewport({
                        scale: 1
                    });
                    syncFullscreenState();
                    const layout = computeLayout(rawViewport);

                    const pageDivs = flipbookEl.querySelectorAll('.page');

                    for (let i = 1; i <= pageCount; i++) {
                        setStatus(`Rendering page ${i} of ${pageCount}…`);
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({
                            scale: layout.canvasScale
                        });

                        if (currentRenderGeneration !== renderGeneration) {
                            return;
                        }

                        const pageDiv = pageDivs[i - 1];
                        pageDiv.style.width = layout.pageWidth + 'px';
                        pageDiv.style.height = layout.pageHeight + 'px';

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

                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.turn !== 'function') {
                        flipbookEl.style.width = layout.pageWidth + 'px';
                        flipbookEl.style.height = 'auto';
                        setStatus('Turn.js failed to load. Showing a static preview.');
                        return;
                    }

                    $flipbook = $('#flipbook');
                    flipbookEl.style.width = layout.bookWidth + 'px';
                    flipbookEl.style.height = layout.bookHeight + 'px';
                    $flipbook.turn({
                        width: layout.bookWidth,
                        height: layout.bookHeight,
                        autoCenter: true,
                        display: layout.display,
                        duration: settings.duration,
                        acceleration: settings.acceleration,
                        gradients: settings.gradients,
                        elevation: settings.elevation,
                    });

                    // Update page info on turn
                    $flipbook.bind('turned', function(event, page) {
                        updatePageInfo();
                    });

                    if (currentPage > 1) {
                        $flipbook.turn('page', Math.min(currentPage, pageCount));
                    }

                    updatePageInfo();
                    setNavigationEnabled(true);

                    if (!keyboardBound) {
                        window.addEventListener('keydown', (e) => {
                            if (!hasActiveFlipbook()) {
                                return;
                            }

                            if (e.key === 'ArrowLeft') {
                                $flipbook.turn('previous');
                            }

                            if (e.key === 'ArrowRight') {
                                $flipbook.turn('next');
                            }

                            if (e.key === 'Escape' && isFullscreen) {
                                exitFullscreen();
                            }
                        });
                        keyboardBound = true;
                    }
                }

                async function enterFullscreen() {
                    if (!previewCardEl?.requestFullscreen) {
                        return;
                    }

                    await previewCardEl.requestFullscreen();
                }

                async function exitFullscreen() {
                    if (document.fullscreenElement) {
                        await document.exitFullscreen();
                    }
                }

                // Share functionality
                shareButtonEl?.addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(shareModalEl);
                    shareModal.show();
                });

                copyLinkButtonEl.addEventListener('click', function() {
                    shareLinkEl.select();
                    shareLinkEl.setSelectionRange(0, 99999);

                    navigator.clipboard.writeText(shareLinkEl.value).then(function() {
                        copySuccessEl.classList.remove('d-none');
                        setTimeout(function() {
                            copySuccessEl.classList.add('d-none');
                        }, 3000);
                    }).catch(function(err) {
                        alert('Failed to copy link: ' + err);
                    });
                });

                previousButtonEl.addEventListener('click', function() {
                    if (hasActiveFlipbook()) {
                        $flipbook.turn('previous');
                    }
                });

                nextButtonEl.addEventListener('click', function() {
                    if (hasActiveFlipbook()) {
                        $flipbook.turn('next');
                    }
                });

                fullscreenButtonEl.addEventListener('click', function() {
                    if (isFullscreen) {
                        exitFullscreen();
                        return;
                    }

                    enterFullscreen();
                });

                document.addEventListener('fullscreenchange', function() {
                    syncFullscreenState();
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(renderAll, 80);
                });

                window.addEventListener('resize', () => {
                    syncFullscreenState();
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(() => renderAll(), 120);
                });

                syncFullscreenState();

                // Initial render
                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
