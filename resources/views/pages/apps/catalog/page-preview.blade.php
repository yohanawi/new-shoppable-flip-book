<x-default-layout>

    @section('title')
        Page Management - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.page-management', $pdf) }}
    @endsection

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.manage', $pdf) }}" class="btn btn-dark border btn-active-light-primary">
                <i class="bi bi-arrow-left"></i> Back to Page Management
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0" id="previewCard">
        <div class="card-body d-flex flex-column gap-5" id="previewCardBody">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4">
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-light" id="btnPrev">Previous</button>
                    <button type="button" class="btn btn-light" id="btnNext">Next</button>
                    <span class="text-muted fs-7 fw-semibold" id="pageInfo"></span>
                </div>

                <div class="text-muted fs-7 fw-semibold" id="status">Rendering pages…</div>

                <div class="d-flex flex-wrap gap-2">
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

            <div id="flipbookStage"
                class="d-flex justify-content-center align-items-center bg-light rounded-4 overflow-auto p-5 flex-grow-1">
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
                    <div class="input-group mt-4">
                        <input type="text" class="form-control" id="shareLink" readonly
                            value="{{ route('catalog.pdfs.share', $pdf) }}">
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

    @push('styles')
        <style>
            #previewCard {
                min-height: calc(100vh - 240px);
            }

            #previewCardBody {
                min-height: inherit;
            }

            #flipbookStage {
                min-height: calc(100vh - 150px);
            }

            #previewCard:fullscreen,
            #previewCard:-webkit-full-screen {
                width: 100vw;
                height: 100vh;
                margin: 0;
            }

            #previewCard:fullscreen #previewCardBody,
            #previewCard:-webkit-full-screen #previewCardBody {
                height: 100%;
            }

            #previewCard:fullscreen #flipbookStage,
            #previewCard:-webkit-full-screen #flipbookStage {
                height: 100%;
                min-height: 0;
            }

            #flipbook {
                margin: 0 auto;
            }

            #flipbook .page {
                background: #fff;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            }

            #flipbook canvas {
                width: 100%;
                height: 100%;
                display: block;
            }

            .flipbook-page-loader {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #f8fafc, #eef2f7);
                color: #64748b;
                font-size: 0.875rem;
                font-weight: 600;
                letter-spacing: 0.01em;
                transition: opacity 0.2s ease;
                z-index: 2;
            }

            .flipbook-page-loader.d-none {
                opacity: 0;
                pointer-events: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/plugins/custom/turnjs/turn.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                let pages = @json($pages);

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

                const hasDbPages = Array.isArray(pages) && pages.length > 0;
                let isFullscreen = false;
                let $flipbook;
                let pdfDocumentPromise = null;
                let pdfDocument = null;
                let renderGeneration = 0;
                let keyboardBound = false;
                let resizeTimer = null;
                let backgroundRenderTimer = null;
                let activeLayout = null;
                let pageShells = [];
                const inFlightRenders = new Map();

                // Configure PDF.js worker
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                function setStatus(text) {
                    statusEl.textContent = text;
                }

                function hasActiveFlipbook(instance = $flipbook) {
                    if (!instance || typeof instance.turn !== 'function') {
                        return false;
                    }

                    try {
                        const currentPage = Number(instance.turn('page'));
                        const totalPages = Number(instance.turn('pages'));
                        return Number.isFinite(currentPage) && Number.isFinite(totalPages) && totalPages > 0;
                    } catch (error) {
                        return false;
                    }
                }

                function updatePageInfo() {
                    if (!hasActiveFlipbook() || !pages || pages.length === 0) return;

                    const currentPage = $flipbook.turn('page');
                    const totalPages = $flipbook.turn('pages');
                    pageInfoEl.textContent = `Page ${currentPage} of ${totalPages}`;
                }

                function navigateFlipbook(direction) {
                    if (!hasActiveFlipbook()) {
                        setStatus('Flipbook is still loading.');
                        return;
                    }

                    const currentPage = $flipbook.turn('page');
                    const totalPages = $flipbook.turn('pages');
                    const targetPage = direction === 'previous' ? Math.max(1, currentPage - 1) : Math.min(totalPages,
                        currentPage + 1);

                    if (targetPage === currentPage) {
                        return;
                    }

                    $flipbook.turn('page', targetPage);
                }

                function normalizePages(pageCount) {
                    if (hasDbPages) {
                        pages = pages.slice().sort(function(left, right) {
                            return Number(left.display_order || left.page_number || 0) - Number(right
                                .display_order ||
                                right.page_number || 0);
                        });
                        return pages;
                    }

                    pages = [];
                    for (let i = 1; i <= pageCount; i++) {
                        pages.push({
                            page_number: i,
                            title: '',
                            display_order: i
                        });
                    }

                    return pages;
                }

                function getActivePageNumber() {
                    if (!hasActiveFlipbook() || !pages || pages.length === 0) {
                        return Number(pages?.[0]?.page_number || 1);
                    }

                    const turnPage = $flipbook.turn('page');
                    return Number(pages[turnPage - 1]?.page_number || pages[0]?.page_number || 1);
                }

                function resolveTurnPageForPageNumber(pageNumber) {
                    const index = pages.findIndex((page) => Number(page.page_number) === Number(pageNumber));
                    return index >= 0 ? index + 1 : 1;
                }

                function buildPageShells() {
                    flipbookEl.innerHTML = '';
                    pageShells = [];

                    for (const p of pages) {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page bg-white overflow-hidden shadow rounded-3 position-relative';
                        pageDiv.dataset.pageNumber = String(p.page_number);

                        const canvas = document.createElement('canvas');
                        canvas.className = 'w-100 h-100 d-block';
                        canvas.width = 10;
                        canvas.height = 10;
                        pageDiv.appendChild(canvas);

                        const loader = document.createElement('div');
                        loader.className = 'flipbook-page-loader';
                        loader.textContent = 'Loading page…';
                        pageDiv.appendChild(loader);

                        // Optional title overlay
                        if (p.title) {
                            const label = document.createElement('div');
                            label.className =
                                'position-absolute start-0 bottom-0 ms-3 mb-3 px-3 py-2 rounded bg-dark bg-opacity-75 text-white fs-8 fw-semibold';
                            label.textContent = p.title;
                            pageDiv.appendChild(label);
                        }

                        flipbookEl.appendChild(pageDiv);
                        pageShells.push(pageDiv);
                    }
                }

                function resetFlipbookElement() {
                    if (!flipbookEl?.parentNode) {
                        return;
                    }

                    const nextFlipbookEl = document.createElement('div');
                    nextFlipbookEl.id = 'flipbook';
                    flipbookEl.parentNode.replaceChild(nextFlipbookEl, flipbookEl);
                    flipbookEl = nextFlipbookEl;
                }

                function destroyTurnIfExists() {
                    window.clearTimeout(backgroundRenderTimer);
                    inFlightRenders.clear();

                    try {
                        if (window.jQuery) {
                            const $currentFlipbook = $('#flipbook');
                            if (hasActiveFlipbook($currentFlipbook)) {
                                $currentFlipbook.turn('stop');
                            }
                        }
                    } catch (error) {
                        console.error(error);
                    }

                    resetFlipbookElement();
                    $flipbook = null;
                    pageShells = [];
                    pageInfoEl.textContent = '';
                }

                function computeLayout(pageViewport) {
                    const stageRect = flipbookStageEl.getBoundingClientRect();
                    const stageStyles = window.getComputedStyle(flipbookStageEl);
                    const horizontalPadding = parseFloat(stageStyles.paddingLeft || '0') + parseFloat(stageStyles
                        .paddingRight || '0');
                    const verticalPadding = parseFloat(stageStyles.paddingTop || '0') + parseFloat(stageStyles
                        .paddingBottom || '0');

                    const availableWidth = Math.max(stageRect.width - horizontalPadding, 280);
                    const availableHeight = Math.max(stageRect.height - verticalPadding, 600);
                    const display = availableWidth < 992 ? 'single' : 'double';
                    const maxSingleWidth = display === 'double' ? availableWidth / 2 : availableWidth;
                    const fitScale = Math.max(
                        Math.min(maxSingleWidth / pageViewport.width, availableHeight / pageViewport.height),
                        0.2
                    );
                    const renderScale = Math.min(Math.max(window.devicePixelRatio || 1, 1), 1.5);

                    const pageWidth = Math.floor(pageViewport.width * fitScale);
                    const pageHeight = Math.floor(pageViewport.height * fitScale);

                    return {
                        pageWidth,
                        pageHeight,
                        bookWidth: display === 'double' ? pageWidth * 2 : pageWidth,
                        bookHeight: pageHeight,
                        canvasScale: fitScale * renderScale,
                        display,
                        key: [display, pageWidth, pageHeight, renderScale.toFixed(2)].join(':')
                    };
                }

                function getPageShell(turnPage) {
                    return pageShells[turnPage - 1] || null;
                }

                function setPageLoaderState(pageDiv, text, isVisible) {
                    const loader = pageDiv?.querySelector('.flipbook-page-loader');
                    if (!loader) {
                        return;
                    }

                    loader.textContent = text;
                    loader.classList.toggle('d-none', !isVisible);
                }

                function getPriorityTurnPages(turnPage) {
                    const totalPages = pages.length;
                    const candidates = [turnPage, turnPage + 1, turnPage - 1, turnPage + 2, turnPage - 2];
                    const ordered = [];
                    const seen = new Set();

                    candidates.forEach(function(candidate) {
                        if (candidate < 1 || candidate > totalPages || seen.has(candidate)) {
                            return;
                        }

                        seen.add(candidate);
                        ordered.push(candidate);
                    });

                    return ordered;
                }

                async function renderTurnPage(turnPage, generation) {
                    if (generation !== renderGeneration || !activeLayout) {
                        return;
                    }

                    const pageMeta = pages[turnPage - 1];
                    const pageDiv = getPageShell(turnPage);
                    if (!pageMeta || !pageDiv) {
                        return;
                    }

                    const layoutKey = activeLayout.key;
                    if (inFlightRenders.get(turnPage) === layoutKey || pageDiv.dataset.renderedLayout === layoutKey) {
                        return;
                    }

                    inFlightRenders.set(turnPage, layoutKey);
                    setPageLoaderState(pageDiv, 'Loading page…', true);

                    try {
                        const page = await pdfDocument.getPage(pageMeta.page_number);
                        if (generation !== renderGeneration || !activeLayout || activeLayout.key !== layoutKey) {
                            return;
                        }

                        const viewport = page.getViewport({
                            scale: activeLayout.canvasScale
                        });
                        const canvas = pageDiv.querySelector('canvas');
                        const context = canvas.getContext('2d');

                        pageDiv.style.width = activeLayout.pageWidth + 'px';
                        pageDiv.style.height = activeLayout.pageHeight + 'px';
                        canvas.width = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);

                        await page.render({
                            canvasContext: context,
                            viewport
                        }).promise;

                        if (generation !== renderGeneration || !activeLayout || activeLayout.key !== layoutKey) {
                            return;
                        }

                        pageDiv.dataset.renderedLayout = layoutKey;
                        setPageLoaderState(pageDiv, '', false);
                    } catch (error) {
                        console.error(error);
                        setPageLoaderState(pageDiv, 'Failed to render page', true);
                    } finally {
                        if (inFlightRenders.get(turnPage) === layoutKey) {
                            inFlightRenders.delete(turnPage);
                        }
                    }
                }

                async function renderVisibleSpread(turnPage, generation) {
                    const priorityPages = getPriorityTurnPages(turnPage);
                    const visiblePages = priorityPages.slice(0, activeLayout?.display === 'double' ? 2 : 1);
                    const preloadPages = priorityPages.slice(visiblePages.length);

                    for (const pageNumber of visiblePages) {
                        await renderTurnPage(pageNumber, generation);
                    }

                    if (!preloadPages.length) {
                        return;
                    }

                    let queueIndex = 0;
                    const pump = async function() {
                        if (generation !== renderGeneration) {
                            return;
                        }

                        const nextPage = preloadPages[queueIndex++];
                        if (!nextPage) {
                            return;
                        }

                        await renderTurnPage(nextPage, generation);

                        if (queueIndex < preloadPages.length) {
                            backgroundRenderTimer = window.setTimeout(pump, 0);
                        }
                    };

                    window.clearTimeout(backgroundRenderTimer);
                    backgroundRenderTimer = window.setTimeout(pump, 0);
                }

                function scheduleRender(delay = 120) {
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(function() {
                        renderAll();
                    }, delay);
                }

                async function loadPdfDocument() {
                    if (pdfDocument) {
                        return pdfDocument;
                    }

                    if (!window.pdfjsLib) {
                        throw new Error('PDF.js failed to load.');
                    }

                    if (!pdfDocumentPromise) {
                        pdfDocumentPromise = window.pdfjsLib.getDocument(pdfUrl).promise;
                    }

                    pdfDocument = await pdfDocumentPromise;
                    return pdfDocument;
                }

                async function renderAll() {
                    const currentGeneration = ++renderGeneration;
                    const activePageNumber = getActivePageNumber();
                    const hasTurnJs = !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.turn ===
                        'function');

                    setStatus('Loading PDF…');

                    try {
                        pdfDocument = await loadPdfDocument();
                    } catch (e) {
                        console.error(e);
                        setStatus('Failed to load PDF. Open the source URL to verify it returns a PDF: ' + pdfUrl);
                        return;
                    }

                    normalizePages(pdfDocument.numPages || 0);

                    if (!pages || pages.length === 0) {
                        setStatus('No pages to preview.');
                        return;
                    }

                    destroyTurnIfExists();

                    const firstPageNo = pages[0].page_number;
                    const firstPage = await pdfDocument.getPage(firstPageNo);
                    const rawViewport = firstPage.getViewport({
                        scale: 1
                    });
                    activeLayout = computeLayout(rawViewport);

                    buildPageShells();

                    Array.from(flipbookEl.children).forEach(function(pageDiv) {
                        pageDiv.style.width = activeLayout.pageWidth + 'px';
                        pageDiv.style.height = activeLayout.pageHeight + 'px';
                        pageDiv.dataset.renderedLayout = '';
                    });

                    if (!hasTurnJs) {
                        await renderVisibleSpread(1, currentGeneration);
                        flipbookEl.style.width = activeLayout.pageWidth + 'px';
                        flipbookEl.style.height = 'auto';
                        setStatus('Turn.js failed to load. Showing a static preview.');
                        return;
                    }

                    if (currentGeneration !== renderGeneration) {
                        return;
                    }

                    setStatus('Ready');

                    try {
                        $flipbook = $('#flipbook');
                        flipbookEl.style.width = activeLayout.bookWidth + 'px';
                        flipbookEl.style.height = activeLayout.bookHeight + 'px';
                        $flipbook.turn({
                            width: activeLayout.bookWidth,
                            height: activeLayout.bookHeight,
                            autoCenter: true,
                            display: activeLayout.display,
                            acceleration: true,
                            gradients: true,
                        });

                        $flipbook.bind('turned', function() {
                            updatePageInfo();
                            renderVisibleSpread($flipbook.turn('page'), renderGeneration);
                        });

                        const targetTurnPage = resolveTurnPageForPageNumber(activePageNumber);
                        if (targetTurnPage > 1) {
                            $flipbook.turn('page', targetTurnPage);
                        }

                        updatePageInfo();
                        await renderVisibleSpread(targetTurnPage, currentGeneration);
                        setStatus('Ready');
                    } catch (e) {
                        console.error(e);
                        setStatus('Rendered, but flip effect failed to initialize.');
                    }
                }

                // Fullscreen functionality
                async function enterFullscreen() {
                    if (!previewCardEl?.requestFullscreen) {
                        return;
                    }

                    try {
                        await previewCardEl.requestFullscreen();
                    } catch (error) {
                        console.error(error);
                    }
                }

                async function exitFullscreen() {
                    if (document.fullscreenElement) {
                        try {
                            await document.exitFullscreen();
                        } catch (error) {
                            console.error(error);
                        }
                    }
                }

                function syncFullscreenState() {
                    isFullscreen = document.fullscreenElement === previewCardEl;

                    previewCardEl.classList.toggle('bg-dark', isFullscreen);
                    previewCardEl.classList.toggle('rounded-0', isFullscreen);
                    previewCardEl.classList.toggle('h-100', isFullscreen);
                    previewCardBodyEl.classList.toggle('h-100', isFullscreen);
                    flipbookStageEl.classList.toggle('bg-dark', isFullscreen);
                    flipbookStageEl.classList.toggle('rounded-0', isFullscreen);
                    flipbookStageEl.classList.toggle('h-100', isFullscreen);
                    flipbookStageEl.classList.toggle('p-8', isFullscreen);
                    flipbookStageEl.classList.toggle('p-5', !isFullscreen);
                    statusEl.classList.toggle('text-white', isFullscreen);
                    statusEl.classList.toggle('text-muted', !isFullscreen);
                    pageInfoEl.classList.toggle('text-white', isFullscreen);
                    pageInfoEl.classList.toggle('text-muted', !isFullscreen);

                    fullscreenButtonEl.innerHTML = isFullscreen ?
                        '<i class="bi bi-fullscreen-exit"></i> Exit Fullscreen' :
                        '<i class="bi bi-fullscreen"></i> Fullscreen';
                }

                // Share functionality
                shareButtonEl.addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(shareModalEl);
                    shareModal.show();
                });

                copyLinkButtonEl.addEventListener('click', function() {
                    shareLinkEl.select();
                    shareLinkEl.setSelectionRange(0, 99999);

                    const showCopySuccess = function() {
                        copySuccessEl.classList.remove('d-none');
                        window.setTimeout(function() {
                            copySuccessEl.classList.add('d-none');
                        }, 3000);
                    };

                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        navigator.clipboard.writeText(shareLinkEl.value).then(showCopySuccess).catch(function() {
                            document.execCommand('copy');
                            showCopySuccess();
                        });
                        return;
                    }

                    document.execCommand('copy');
                    showCopySuccess();
                });

                fullscreenButtonEl.addEventListener('click', () => {
                    if (isFullscreen) {
                        exitFullscreen();
                        return;
                    }

                    enterFullscreen();
                });

                previousButtonEl.onclick = function() {
                    navigateFlipbook('previous');
                };

                nextButtonEl.onclick = function() {
                    navigateFlipbook('next');
                };

                if (!keyboardBound) {
                    window.addEventListener('keydown', function(e) {
                        if (!hasActiveFlipbook()) {
                            return;
                        }

                        if (e.key === 'ArrowLeft') {
                            navigateFlipbook('previous');
                        }

                        if (e.key === 'ArrowRight') {
                            navigateFlipbook('next');
                        }

                        if (e.key === 'Escape' && isFullscreen) {
                            exitFullscreen();
                        }
                    });
                    keyboardBound = true;
                }

                document.addEventListener('fullscreenchange', function() {
                    syncFullscreenState();
                    scheduleRender(80);
                });

                window.addEventListener('resize', function() {
                    scheduleRender(120);
                });

                renderAll();
            })();
        </script>
    @endpush

</x-default-layout>
