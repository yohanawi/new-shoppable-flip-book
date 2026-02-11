<x-default-layout>

    @section('title')
        Slicer (Shoppable) Editor - {{ $pdf->title }}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.slicer.edit', $pdf) }}
    @endsection

    <style>
        .slicer-canvas-wrap {
            width: 100%;
            overflow: auto;
            border: 1px dashed var(--bs-gray-300);
            border-radius: 0.475rem;
            background: var(--bs-body-bg);
        }

        .slicer-canvas-toolbar {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .hotspot-list {
            max-height: 320px;
            overflow: auto;
        }

        .hotspot-list .list-group-item {
            cursor: pointer;
        }

        #thumbnailPreview {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 2px solid var(--bs-gray-300);
            border-radius: 0.475rem;
            overflow: hidden;
        }

        #thumbnailPreview img,
        #thumbnailPreview canvas {
            width: 100%;
            display: block;
        }

        .thumbnail-hotspot {
            position: absolute;
            border: 2px solid rgba(var(--bs-primary-rgb), 0.85);
            background: rgba(var(--bs-primary-rgb), 0.15);
            pointer-events: none;
            transition: all 0.2s ease;
        }

        .thumbnail-hotspot.active {
            border-color: rgba(var(--bs-success-rgb), 1);
            background: rgba(var(--bs-success-rgb), 0.2);
        }
    </style>

    <div class="d-flex flex-wrap flex-stack mb-6">
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light btn-active-light-primary">Back</a>
            <a href="{{ route('catalog.pdfs.slicer.preview', $pdf) }}" class="btn btn-light-primary">Shoppable Preview</a>
            <form method="POST" action="{{ route('catalog.pdfs.slicer.generate-images', $pdf) }}">
                @csrf
                <button type="submit" class="btn btn-light-success">Generate Page Images</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-secondary">
        <div class="fw-semibold mb-1">Process</div>
        <div class="text-gray-700">PDF → Images → Clickable areas → Shoppable (Turn.js).</div>
        <div class="text-muted mt-2">
            Note: Server-side image generation uses Spatie PDF-to-Image (requires PHP Imagick + Ghostscript). If not
            available,
            the editor will fall back to client-side rendering via PDF.js.
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="card-title d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted fw-semibold">Page</span>
                            <select class="form-select form-select-sm" id="pageSelect" style="min-width: 220px;">
                                @foreach ($pages as $p)
                                    <option value="{{ $p->id }}" data-page-number="{{ $p->page_number }}">
                                        {{ $p->title ?: 'Page ' . $p->page_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="slicer-canvas-toolbar">
                            <span class="text-muted fw-semibold">Tool</span>
                            <button type="button" class="btn btn-sm btn-light" data-tool="rectangle"
                                id="toolRect">Rectangle</button>
                            <button type="button" class="btn btn-sm btn-light" data-tool="polygon"
                                id="toolPoly">Polygon</button>
                            <button type="button" class="btn btn-sm btn-light" data-tool="free"
                                id="toolFree">Free</button>
                            <div class="vr"></div>
                            <button type="button" class="btn btn-sm btn-light" id="btnClear">Clear Selection</button>
                            <button type="button" class="btn btn-sm btn-light" id="btnDeleteSelected">Delete
                                Selected</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($pages->count() === 0)
                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold">No pages initialized.</div>
                            <div class="text-muted">Click Initialize Pages to count pages in the browser (PDF.js) and
                                create page rows in the database.</div>
                            <form id="initPagesForm" method="POST"
                                action="{{ route('catalog.pdfs.slicer.pages.init', $pdf) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="page_count" id="pageCountInput" value="">
                                <button type="button" class="btn btn-warning" id="btnInitPages">Initialize
                                    Pages</button>
                            </form>
                        </div>
                    @else
                        <div class="slicer-canvas-wrap p-3">
                            <canvas id="slicerCanvas"></canvas>
                        </div>
                        <div class="text-muted mt-3">Draw a hotspot area on the page, then fill the details and save.
                        </div>

                        <!-- Thumbnail Preview -->
                        <div class="mt-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">Thumbnail Preview</h5>
                                <button type="button" class="btn btn-sm btn-light" id="btnRefreshPreview">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <div id="thumbnailPreview">
                                <canvas id="thumbnailCanvas"></canvas>
                                <div id="thumbnailHotspots"></div>
                            </div>
                            <div class="text-muted mt-2 small">Shows all hotspots on this page with borders</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-5">
                <div class="card-header">
                    <div class="card-title">Hotspot Details</div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light mb-4">
                        <div class="fw-semibold">Step 1</div>
                        <div class="text-muted">Choose a tool and draw on the page.</div>
                        <div class="fw-semibold mt-3">Step 2</div>
                        <div class="text-muted">Select an action type and enter details.</div>
                    </div>

                    <form id="hotspotForm">
                        @csrf
                        <input type="hidden" id="hotspotId" value="">
                        <input type="hidden" name="shape_type" id="shapeType" value="rectangle">
                        <input type="hidden" name="shape_data" id="shapeData" value="">
                        <input type="hidden" name="x" id="bboxX" value="">
                        <input type="hidden" name="y" id="bboxY" value="">
                        <input type="hidden" name="w" id="bboxW" value="">
                        <input type="hidden" name="h" id="bboxH" value="">

                        <div class="mb-4">
                            <label class="form-label">Action</label>
                            <select class="form-select" name="action_type" id="actionType">
                                @foreach ($actionOptions as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active"
                                id="isActive" checked>
                            <label class="form-check-label" for="isActive">Status (Active)</label>
                        </div>

                        <div class="mb-4 common-field">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="title"
                                placeholder="Title">
                        </div>

                        <div class="mb-4 common-field">
                            <label class="form-label">Color</label>
                            <input type="text" class="form-control" name="color" id="color"
                                placeholder="#FF0000 or any label">
                        </div>

                        <div class="mb-4 link-field">
                            <label class="form-label">Link</label>
                            <input type="text" class="form-control" name="link" id="link"
                                placeholder="https://...">
                            <div class="form-text">For Internal Page, you can also set the page number below.</div>
                        </div>

                        <div class="mb-4 internal-field">
                            <label class="form-label">Internal Page Number</label>
                            <input type="number" class="form-control" name="internal_page_number" id="internalPage"
                                min="1" step="1">
                        </div>

                        <div class="mb-4 popup-window-field">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                        </div>

                        <div class="mb-4 popup-window-field">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="price" min="0"
                                step="0.01">
                        </div>

                        <div class="mb-4 thumb-field">
                            <label class="form-label">Thumbnail Image</label>
                            <input type="file" class="form-control" name="thumbnail" id="thumbnail"
                                accept="image/*">
                        </div>

                        <div class="mb-4 popup-image-field">
                            <label class="form-label">Popup Image</label>
                            <input type="file" class="form-control" name="popup_image" id="popupImage"
                                accept="image/*">
                        </div>

                        <div class="mb-4 popup-video-field">
                            <label class="form-label">Popup Video (Upload)</label>
                            <input type="file" class="form-control" name="popup_video" id="popupVideo"
                                accept="video/mp4,video/webm">
                        </div>

                        <div class="mb-4 popup-video-field">
                            <label class="form-label">Popup Video URL</label>
                            <input type="text" class="form-control" name="popup_video_url" id="popupVideoUrl"
                                placeholder="https://...">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btnSave">Save Hotspot</button>
                            <button type="button" class="btn btn-light" id="btnNew">New</button>
                        </div>

                        <div class="text-muted mt-3" id="saveStatus"></div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="card-title">Hotspots on Page</div>
                </div>
                <div class="card-body">
                    <div class="hotspot-list">
                        <div class="list-group" id="hotspotList"></div>
                    </div>
                    <div class="text-muted mt-3">Click a hotspot to edit. Use Delete Selected to remove from canvas,
                        then save.</div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
        <script>
            (function() {
                const pdfUrl = @json($pdfUrl);
                const pages = @json($pages);

                // PDF.js worker
                if (window.pdfjsLib) {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                const pageSelect = document.getElementById('pageSelect');
                const hotspotList = document.getElementById('hotspotList');
                const saveStatus = document.getElementById('saveStatus');

                const formEl = document.getElementById('hotspotForm');
                const hotspotIdEl = document.getElementById('hotspotId');

                const shapeTypeEl = document.getElementById('shapeType');
                const shapeDataEl = document.getElementById('shapeData');
                const bboxXEl = document.getElementById('bboxX');
                const bboxYEl = document.getElementById('bboxY');
                const bboxWEl = document.getElementById('bboxW');
                const bboxHEl = document.getElementById('bboxH');

                const actionTypeEl = document.getElementById('actionType');

                let currentTool = 'rectangle';
                let canvas;
                let backgroundLoaded = false;
                let currentObject = null;
                let polygonPoints = [];
                let polygonTemp = null;
                let pdfDoc = null;

                function setStatus(text) {
                    saveStatus.textContent = text;
                }

                function showFieldsForAction(action) {
                    const isInternal = action === 'internal_page';
                    const isExternal = action === 'external_link';
                    const isPopupWindow = action === 'popup_window';
                    const isPopupImage = action === 'popup_image';
                    const isPopupVideo = action === 'popup_video';

                    const setDisplay = (selector, show) => {
                        document.querySelectorAll(selector).forEach(el => {
                            el.style.display = show ? '' : 'none';
                        });
                    };

                    // Common fields appear for link/popup_window/popups, but not strictly required
                    setDisplay('.common-field', !(isPopupImage || isPopupVideo));
                    setDisplay('.link-field', isInternal || isExternal || isPopupWindow);
                    setDisplay('.internal-field', isInternal);
                    setDisplay('.popup-window-field', isPopupWindow);
                    setDisplay('.thumb-field', isInternal || isExternal || isPopupWindow);
                    setDisplay('.popup-image-field', isPopupImage);
                    setDisplay('.popup-video-field', isPopupVideo);
                }

                function clearSelection() {
                    if (!canvas) return;
                    canvas.getObjects().forEach(o => canvas.remove(o));
                    canvas.renderAll();
                    currentObject = null;
                    polygonPoints = [];
                    polygonTemp = null;
                    shapeDataEl.value = '';
                    bboxXEl.value = '';
                    bboxYEl.value = '';
                    bboxWEl.value = '';
                    bboxHEl.value = '';
                }

                function deleteSelected() {
                    if (!canvas) return;
                    const obj = canvas.getActiveObject();
                    if (obj) {
                        canvas.remove(obj);
                        canvas.discardActiveObject();
                        canvas.renderAll();
                        currentObject = null;
                    }
                }

                function normalizeBbox(rect) {
                    const w = canvas.getWidth();
                    const h = canvas.getHeight();
                    const x = Math.max(0, Math.min(1, rect.left / w));
                    const y = Math.max(0, Math.min(1, rect.top / h));
                    const ww = Math.max(0, Math.min(1, rect.width / w));
                    const hh = Math.max(0, Math.min(1, rect.height / h));
                    return {
                        x,
                        y,
                        w: ww,
                        h: hh
                    };
                }

                function setCurrentObject(obj, shapeType) {
                    currentObject = obj;
                    shapeTypeEl.value = shapeType;

                    const bbox = obj.getBoundingRect(true, true);
                    const n = normalizeBbox(bbox);
                    bboxXEl.value = n.x.toFixed(6);
                    bboxYEl.value = n.y.toFixed(6);
                    bboxWEl.value = n.w.toFixed(6);
                    bboxHEl.value = n.h.toFixed(6);

                    const data = obj.toObject(['path', 'points', 'rx', 'ry']);
                    data.__meta = {
                        canvasWidth: canvas.getWidth(),
                        canvasHeight: canvas.getHeight()
                    };
                    shapeDataEl.value = JSON.stringify(data);
                }

                function setTool(tool) {
                    currentTool = tool;
                    if (!canvas) return;

                    canvas.isDrawingMode = tool === 'free';
                    if (canvas.isDrawingMode) {
                        canvas.freeDrawingBrush.width = 3;
                        canvas.freeDrawingBrush.color = 'rgba(0, 120, 255, 0.8)';
                    }

                    polygonPoints = [];
                    if (polygonTemp) {
                        canvas.remove(polygonTemp);
                        polygonTemp = null;
                    }

                    document.querySelectorAll('[data-tool]').forEach(btn => {
                        btn.classList.toggle('btn-primary', btn.getAttribute('data-tool') === tool);
                        btn.classList.toggle('btn-light', btn.getAttribute('data-tool') !== tool);
                    });
                }

                async function ensurePdfLoaded() {
                    if (pdfDoc) return pdfDoc;
                    pdfDoc = await window.pdfjsLib.getDocument(pdfUrl).promise;
                    return pdfDoc;
                }

                function setCanvasSizeFromImage(img, maxWidth = 980) {
                    const ratio = img.width / img.height;
                    const w = Math.min(maxWidth, img.width);
                    const h = Math.round(w / ratio);
                    canvas.setWidth(w);
                    canvas.setHeight(h);
                }

                function setBackgroundFromUrl(url) {
                    return new Promise((resolve) => {
                        fabric.Image.fromURL(url, (img) => {
                            setCanvasSizeFromImage(img);
                            img.scaleToWidth(canvas.getWidth());
                            canvas.setBackgroundImage(img, () => {
                                backgroundLoaded = true;
                                canvas.renderAll();
                                resolve(true);
                            });
                        }, {
                            crossOrigin: 'anonymous'
                        });
                    });
                }

                async function setBackgroundFromPdfPage(pageNumber) {
                    const pdf = await ensurePdfLoaded();
                    const page = await pdf.getPage(pageNumber);
                    const rawViewport = page.getViewport({
                        scale: 1
                    });
                    const targetW = Math.min(980, rawViewport.width);
                    const scale = targetW / rawViewport.width;
                    const viewport = page.getViewport({
                        scale
                    });

                    const tmp = document.createElement('canvas');
                    tmp.width = Math.floor(viewport.width);
                    tmp.height = Math.floor(viewport.height);
                    await page.render({
                        canvasContext: tmp.getContext('2d'),
                        viewport
                    }).promise;

                    const dataUrl = tmp.toDataURL('image/jpeg', 0.9);
                    await setBackgroundFromUrl(dataUrl);
                }

                function currentPageId() {
                    return pageSelect?.value;
                }

                function currentPageNumber() {
                    const opt = pageSelect?.selectedOptions?.[0];
                    return opt ? parseInt(opt.getAttribute('data-page-number') || '1', 10) : 1;
                }

                function pageImageUrl(pageId) {
                    return @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages')) + '/' + pageId + '/image';
                }

                async function loadBackgroundForPage() {
                    if (!canvas) return;
                    backgroundLoaded = false;
                    clearSelection();

                    const pid = currentPageId();
                    const p = pages.find(x => String(x.id) === String(pid));
                    if (p && p.image_path) {
                        await setBackgroundFromUrl(pageImageUrl(pid));
                        return;
                    }

                    await setBackgroundFromPdfPage(currentPageNumber());
                }

                async function loadHotspots() {
                    const pid = currentPageId();
                    if (!pid) return;

                    hotspotList.innerHTML = '<div class="text-muted">Loading…</div>';
                    const url = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages')) + '/' + pid + '/hotspots';

                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();
                    const data = json.data || [];

                    if (data.length === 0) {
                        hotspotList.innerHTML = '<div class="text-muted">No hotspots yet.</div>';
                        return;
                    }

                    hotspotList.innerHTML = '';
                    for (const h of data) {
                        const item = document.createElement('a');
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = (h.title ? h.title + ' — ' : '') + h.action_type;
                        item.addEventListener('click', () => {
                            loadHotspotIntoForm(h);
                        });
                        hotspotList.appendChild(item);
                    }
                }

                async function renderThumbnailPreview() {
                    const thumbnailCanvas = document.getElementById('thumbnailCanvas');
                    const thumbnailHotspots = document.getElementById('thumbnailHotspots');
                    if (!thumbnailCanvas || !thumbnailHotspots) return;

                    const pid = currentPageId();
                    if (!pid) return;

                    const ctx = thumbnailCanvas.getContext('2d');

                    // Load page image
                    const p = pages.find(x => String(x.id) === String(pid));
                    let imgUrl;
                    if (p && p.image_path) {
                        imgUrl = pageImageUrl(pid);
                    } else {
                        // Render from PDF
                        const pdf = await ensurePdfLoaded();
                        const page = await pdf.getPage(currentPageNumber());
                        const viewport = page.getViewport({
                            scale: 1
                        });
                        const maxWidth = 400;
                        const scale = maxWidth / viewport.width;
                        const scaledViewport = page.getViewport({
                            scale
                        });

                        thumbnailCanvas.width = scaledViewport.width;
                        thumbnailCanvas.height = scaledViewport.height;

                        await page.render({
                            canvasContext: ctx,
                            viewport: scaledViewport
                        }).promise;
                    }

                    // If image exists, load it instead
                    if (imgUrl) {
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        await new Promise((resolve) => {
                            img.onload = () => {
                                const maxWidth = 400;
                                const scale = maxWidth / img.width;
                                thumbnailCanvas.width = Math.floor(img.width * scale);
                                thumbnailCanvas.height = Math.floor(img.height * scale);
                                ctx.drawImage(img, 0, 0, thumbnailCanvas.width, thumbnailCanvas.height);
                                resolve();
                            };
                            img.src = imgUrl;
                        });
                    }

                    // Fetch hotspots
                    const url = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer/pages')) + '/' + pid + '/hotspots';
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json();
                    const hotspots = json.data || [];

                    // Clear existing hotspot overlays
                    thumbnailHotspots.innerHTML = '';

                    // Create hotspot overlays
                    for (const h of hotspots) {
                        const div = document.createElement('div');
                        div.className = 'thumbnail-hotspot';
                        if (h.is_active) div.classList.add('active');

                        // Position using percentage values (x, y, w, h are normalized 0-1)
                        div.style.left = (h.x * 100) + '%';
                        div.style.top = (h.y * 100) + '%';
                        div.style.width = (h.w * 100) + '%';
                        div.style.height = (h.h * 100) + '%';

                        div.title = h.title || h.action_type;
                        thumbnailHotspots.appendChild(div);
                    }
                }

                function scaleObjectData(objData, scaleX, scaleY) {
                    const data = JSON.parse(JSON.stringify(objData));
                    if (typeof data.left === 'number') data.left = data.left * scaleX;
                    if (typeof data.top === 'number') data.top = data.top * scaleY;
                    if (typeof data.scaleX === 'number') data.scaleX = data.scaleX * scaleX;
                    if (typeof data.scaleY === 'number') data.scaleY = data.scaleY * scaleY;

                    if (Array.isArray(data.points)) {
                        data.points = data.points.map(p => ({
                            x: p.x * scaleX,
                            y: p.y * scaleY
                        }));
                    }

                    // For free-draw path, Fabric keeps path coords in data.path
                    if (Array.isArray(data.path)) {
                        data.path = data.path.map(cmd => {
                            if (!Array.isArray(cmd)) return cmd;
                            return cmd.map((v, idx) => {
                                if (typeof v !== 'number') return v;
                                // Heuristic: alternate x/y coordinates in path arrays
                                return (idx % 2 === 1) ? v * scaleY : v * scaleX;
                            });
                        });
                    }

                    return data;
                }

                function loadHotspotIntoForm(h) {
                    hotspotIdEl.value = String(h.id);
                    actionTypeEl.value = h.action_type;
                    showFieldsForAction(h.action_type);

                    document.getElementById('isActive').checked = !!h.is_active;
                    document.getElementById('title').value = h.title || '';
                    document.getElementById('color').value = h.color || '';
                    document.getElementById('link').value = h.link || '';
                    document.getElementById('internalPage').value = h.internal_page_number || '';
                    document.getElementById('description').value = h.description || '';
                    document.getElementById('price').value = h.price || '';

                    // Draw it on canvas
                    clearSelection();
                    const data = h.shape_data;
                    if (!data || !canvas) return;

                    let scaledData = data;
                    if (data.__meta && data.__meta.canvasWidth && data.__meta.canvasHeight) {
                        const sx = canvas.getWidth() / data.__meta.canvasWidth;
                        const sy = canvas.getHeight() / data.__meta.canvasHeight;
                        scaledData = scaleObjectData(data, sx, sy);
                    }

                    fabric.util.enlivenObjects([scaledData], function(objects) {
                        if (!objects || objects.length === 0) return;
                        const obj = objects[0];
                        canvas.add(obj);
                        canvas.setActiveObject(obj);
                        canvas.renderAll();
                        setCurrentObject(obj, h.shape_type);
                    });
                }

                async function saveHotspot() {
                    if (!currentObject || !backgroundLoaded) {
                        setStatus('Draw a hotspot first.');
                        return;
                    }

                    const pid = currentPageId();
                    const isUpdate = !!hotspotIdEl.value;

                    const urlBase = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer'));
                    const url = isUpdate ? (urlBase + '/hotspots/' + hotspotIdEl.value) : (urlBase + '/pages/' + pid +
                        '/hotspots');
                    const method = isUpdate ? 'PATCH' : 'POST';

                    const fd = new FormData(formEl);
                    fd.set('shape_type', shapeTypeEl.value);
                    fd.set('shape_data', shapeDataEl.value);
                    fd.set('x', bboxXEl.value);
                    fd.set('y', bboxYEl.value);
                    fd.set('w', bboxWEl.value);
                    fd.set('h', bboxHEl.value);

                    setStatus('Saving…');

                    const res = await fetch(url, {
                        method,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });

                    if (!res.ok) {
                        let message = 'Save failed.';
                        try {
                            const j = await res.json();
                            message = j.message || message;
                        } catch (e) {}
                        setStatus(message);
                        return;
                    }

                    const json = await res.json();
                    const saved = json.data;
                    hotspotIdEl.value = String(saved.id);

                    setStatus('Saved.');
                    await loadHotspots();
                }

                function setupCanvas() {
                    const el = document.getElementById('slicerCanvas');
                    if (!el) return;

                    canvas = new fabric.Canvas('slicerCanvas', {
                        selection: true,
                        preserveObjectStacking: true
                    });

                    // Start with a reasonable size; background will resize canvas
                    canvas.setWidth(980);
                    canvas.setHeight(720);

                    // Rectangle tool
                    let isDown = false;
                    let startX = 0;
                    let startY = 0;
                    let rect;

                    canvas.on('mouse:down', function(o) {
                        if (!backgroundLoaded) return;

                        if (currentTool === 'rectangle') {
                            isDown = true;
                            const pointer = canvas.getPointer(o.e);
                            startX = pointer.x;
                            startY = pointer.y;

                            rect = new fabric.Rect({
                                left: startX,
                                top: startY,
                                width: 1,
                                height: 1,
                                fill: 'rgba(0,120,255,0.15)',
                                stroke: 'rgba(0,120,255,0.9)',
                                strokeWidth: 2,
                                selectable: true
                            });

                            clearSelection();
                            canvas.add(rect);
                            canvas.setActiveObject(rect);
                        }

                        if (currentTool === 'polygon') {
                            const pointer = canvas.getPointer(o.e);
                            polygonPoints.push({
                                x: pointer.x,
                                y: pointer.y
                            });

                            if (polygonTemp) {
                                canvas.remove(polygonTemp);
                            }

                            polygonTemp = new fabric.Polyline(polygonPoints, {
                                fill: 'rgba(0,120,255,0.12)',
                                stroke: 'rgba(0,120,255,0.9)',
                                strokeWidth: 2,
                                selectable: false,
                                objectCaching: false
                            });

                            clearSelection();
                            canvas.add(polygonTemp);
                            canvas.renderAll();
                        }
                    });

                    canvas.on('mouse:move', function(o) {
                        if (!isDown || currentTool !== 'rectangle' || !rect) return;
                        const pointer = canvas.getPointer(o.e);
                        rect.set({
                            width: Math.abs(pointer.x - startX),
                            height: Math.abs(pointer.y - startY),
                            left: Math.min(pointer.x, startX),
                            top: Math.min(pointer.y, startY)
                        });
                        rect.setCoords();
                        canvas.renderAll();
                    });

                    canvas.on('mouse:up', function() {
                        if (currentTool === 'rectangle' && rect) {
                            isDown = false;
                            setCurrentObject(rect, 'rectangle');
                            rect = null;
                        }
                    });

                    // Finish polygon on double click
                    canvas.upperCanvasEl.addEventListener('dblclick', function() {
                        if (currentTool !== 'polygon' || polygonPoints.length < 3) return;
                        if (polygonTemp) {
                            canvas.remove(polygonTemp);
                            polygonTemp = null;
                        }

                        const poly = new fabric.Polygon(polygonPoints, {
                            fill: 'rgba(0,120,255,0.15)',
                            stroke: 'rgba(0,120,255,0.9)',
                            strokeWidth: 2,
                            selectable: true,
                        });

                        polygonPoints = [];
                        clearSelection();
                        canvas.add(poly);
                        canvas.setActiveObject(poly);
                        canvas.renderAll();
                        setCurrentObject(poly, 'polygon');
                    });

                    // Capture free-draw path
                    canvas.on('path:created', function(opt) {
                        if (currentTool !== 'free') return;
                        const path = opt.path;
                        path.set({
                            fill: 'rgba(0,120,255,0.10)',
                            stroke: 'rgba(0,120,255,0.9)',
                            strokeWidth: 2,
                            selectable: true
                        });

                        clearSelection();
                        canvas.add(path);
                        canvas.setActiveObject(path);
                        canvas.renderAll();
                        setCurrentObject(path, 'free');
                    });
                }

                async function onPageChanged() {
                    if (!canvas) return;
                    hotspotIdEl.value = '';
                    setStatus('');
                    await loadBackgroundForPage();
                    await loadHotspots();
                    await renderThumbnailPreview();
                }

                function resetFormForNew() {
                    hotspotIdEl.value = '';
                    formEl.reset();
                    document.getElementById('isActive').checked = true;
                    showFieldsForAction(actionTypeEl.value);
                    clearSelection();
                    setStatus('');
                }

                // Init Pages (client-side) if needed
                async function initPagesClientSide() {
                    const btn = document.getElementById('btnInitPages');
                    const input = document.getElementById('pageCountInput');
                    const form = document.getElementById('initPagesForm');
                    if (!btn || !input || !form) return;

                    btn.disabled = true;
                    btn.textContent = 'Counting pages…';

                    try {
                        const pdf = await window.pdfjsLib.getDocument(pdfUrl).promise;
                        input.value = String(pdf.numPages || 0);
                        form.submit();
                    } catch (e) {
                        console.error(e);
                        btn.disabled = false;
                        btn.textContent = 'Initialize Pages';
                        alert('Failed to read PDF.');
                    }
                }

                // Wire UI
                actionTypeEl?.addEventListener('change', () => showFieldsForAction(actionTypeEl.value));

                document.getElementById('toolRect')?.addEventListener('click', () => {
                    setTool('rectangle');
                    shapeTypeEl.value = 'rectangle';
                });
                document.getElementById('toolPoly')?.addEventListener('click', () => {
                    setTool('polygon');
                    shapeTypeEl.value = 'polygon';
                });
                document.getElementById('toolFree')?.addEventListener('click', () => {
                    setTool('free');
                    shapeTypeEl.value = 'free';
                });

                document.getElementById('btnClear')?.addEventListener('click', clearSelection);
                document.getElementById('btnDeleteSelected')?.addEventListener('click', deleteSelected);
                document.getElementById('btnNew')?.addEventListener('click', resetFormForNew);
                document.getElementById('btnInitPages')?.addEventListener('click', initPagesClientSide);
                document.getElementById('btnRefreshPreview')?.addEventListener('click', renderThumbnailPreview);

                formEl?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveHotspot();
                });

                // Boot
                showFieldsForAction(actionTypeEl?.value || 'internal_page');
                setupCanvas();
                if (pageSelect && pages && pages.length > 0) {
                    setTool('rectangle');
                    onPageChanged();
                    pageSelect.addEventListener('change', onPageChanged);
                }
            })();
        </script>
    @endpush

</x-default-layout>
