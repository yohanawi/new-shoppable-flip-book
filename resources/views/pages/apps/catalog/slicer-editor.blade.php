<x-default-layout>

    @section('title')
        Slicer Editor
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

        .hotspot-list .list-group-item.active {
            border-color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-gray-900);
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

    <div class="card border-0 shadow-sm overflow-hidden mb-8">
        <div class="card-body p-0">
            <div class="p-10 p-lg-15" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
                <div class="d-flex flex-wrap justify-content-between gap-6 align-items-center">
                    <div class="mw-500px">
                        <span class="badge badge-light-primary mb-4">Slicer</span>
                        <h1 class="text-white fw-bold mb-4">Build hotspots for {{ $pdf->title }}</h1>
                        <div class="text-white opacity-75 fs-5">
                            This screen is for interactive and shoppable hotspots only. Choose the page, draw the
                            hotspot, and save the action data. It uses the same PDF pages managed in Page Management.
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light">Back</a>
                        <a href="{{ route('catalog.pdfs.slicer.preview', $pdf) }}"
                            class="btn btn-light-primary">Shoppable Preview</a>
                        <form method="POST" action="{{ route('catalog.pdfs.slicer.generate-images', $pdf) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-success">Generate Page Images</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-6 g-xl-8 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Current function</div>
                    <div class="fw-bold text-gray-900 fs-3">Slicer</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Pages</div>
                    <div class="fw-bold text-gray-900 fs-3">{{ $pages->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Visibility</div>
                    <div class="fw-bold text-gray-900 fs-3 text-capitalize">{{ $pdf->visibility }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Source file</div>
                    <div class="fw-bold text-gray-900 fs-6">{{ $pdf->original_filename ?: 'Uploaded PDF' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
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
                            <button type="button" class="btn btn-sm btn-light" data-tool="select"
                                id="toolSelect">Select</button>
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
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-header">
                    <div class="card-title">Hotspot Details</div>
                </div>
                <div class="card-body">
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
                            <select class="form-select" name="action_type" id="actionType" data-control="select2"
                                data-hide-search="true">
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
                            <div class="form-text mt-2" id="thumbnailCurrent"></div>
                        </div>

                        <div class="mb-4 popup-image-field">
                            <label class="form-label">Popup Image</label>
                            <input type="file" class="form-control" name="popup_image" id="popupImage"
                                accept="image/*">
                            <div class="form-text mt-2" id="popupImageCurrent"></div>
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
                            <div class="form-text mt-2" id="popupVideoCurrent"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btnSave">Save Hotspot</button>
                            <button type="button" class="btn btn-light" id="btnNew">New</button>
                        </div>

                        <div class="text-muted mt-3" id="saveStatus"></div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
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
                const saveButton = document.getElementById('btnSave');

                const formEl = document.getElementById('hotspotForm');
                const hotspotIdEl = document.getElementById('hotspotId');

                const shapeTypeEl = document.getElementById('shapeType');
                const shapeDataEl = document.getElementById('shapeData');
                const bboxXEl = document.getElementById('bboxX');
                const bboxYEl = document.getElementById('bboxY');
                const bboxWEl = document.getElementById('bboxW');
                const bboxHEl = document.getElementById('bboxH');

                const actionTypeEl = document.getElementById('actionType');
                const thumbnailInput = document.getElementById('thumbnail');
                const popupImageInput = document.getElementById('popupImage');
                const popupVideoInput = document.getElementById('popupVideo');
                const popupVideoUrlEl = document.getElementById('popupVideoUrl');
                const thumbnailCurrentEl = document.getElementById('thumbnailCurrent');
                const popupImageCurrentEl = document.getElementById('popupImageCurrent');
                const popupVideoCurrentEl = document.getElementById('popupVideoCurrent');

                let currentTool = 'rectangle';
                let canvas;
                let backgroundLoaded = false;
                let currentObject = null;
                let polygonPoints = [];
                let polygonTemp = null;
                let pdfDoc = null;

                function setStatus(text, tone = 'muted') {
                    saveStatus.textContent = text;
                    saveStatus.className = 'mt-3';
                    saveStatus.classList.add(
                        tone === 'success' ? 'text-success' :
                        tone === 'danger' ? 'text-danger' :
                        tone === 'warning' ? 'text-warning' : 'text-muted'
                    );
                }

                function setFormMode(isEditing) {
                    if (!saveButton) return;
                    saveButton.textContent = isEditing ? 'Update Hotspot' : 'Save Hotspot';
                }

                function clearFileInputs() {
                    [thumbnailInput, popupImageInput, popupVideoInput].forEach(input => {
                        if (input) {
                            input.value = '';
                        }
                    });
                }

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function setMediaState(element, html, emptyText) {
                    if (!element) return;
                    element.innerHTML = html || `<span class="text-muted">${emptyText}</span>`;
                }

                function updateCurrentMedia(hotspot = null) {
                    setMediaState(
                        thumbnailCurrentEl,
                        hotspot?.thumbnail_url ?
                        `<a href="${hotspot.thumbnail_url}" target="_blank" rel="noopener">Current thumbnail</a>` :
                        '',
                        'No thumbnail uploaded.'
                    );

                    setMediaState(
                        popupImageCurrentEl,
                        hotspot?.popup_image_url ?
                        `<a href="${hotspot.popup_image_url}" target="_blank" rel="noopener">Current popup image</a>` :
                        '',
                        'No popup image uploaded.'
                    );

                    const popupVideoParts = [];
                    if (hotspot?.popup_video_file_url) {
                        popupVideoParts.push(
                            `<a href="${hotspot.popup_video_file_url}" target="_blank" rel="noopener">Current uploaded video</a>`
                        );
                    }
                    if (hotspot?.popup_video_url) {
                        popupVideoParts.push(
                            `Current video URL: <a href="${escapeHtml(hotspot.popup_video_url)}" target="_blank" rel="noopener">${escapeHtml(hotspot.popup_video_url)}</a>`
                        );
                    }

                    setMediaState(
                        popupVideoCurrentEl,
                        popupVideoParts.join('<br>'),
                        'No popup video selected.'
                    );
                }

                function setActionValue(action) {
                    if (!actionTypeEl) return;

                    actionTypeEl.value = action;

                    if (window.jQuery && window.jQuery(actionTypeEl).hasClass('select2-hidden-accessible')) {
                        window.jQuery(actionTypeEl).val(action).trigger('change');
                        return;
                    }

                    actionTypeEl.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                function setActiveHotspotListItem(hotspotId = null) {
                    if (!hotspotList) return;

                    hotspotList.querySelectorAll('[data-hotspot-id]').forEach(item => {
                        item.classList.toggle('active', hotspotId !== null && item.dataset.hotspotId === String(
                            hotspotId));
                    });
                }

                function currentActionValue() {
                    return actionTypeEl?.value || 'internal_page';
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

                function syncActionFields() {
                    showFieldsForAction(currentActionValue());
                }

                function bindActionTypeEvents() {
                    if (!actionTypeEl) return;

                    ['change', 'input'].forEach(eventName => {
                        actionTypeEl.addEventListener(eventName, syncActionFields);
                    });

                    if (window.jQuery) {
                        window.jQuery(actionTypeEl).on(
                            'change select2:select select2:clear select2:close',
                            syncActionFields
                        );
                    }

                    // Some theme scripts initialize Select2 after this script runs.
                    window.setTimeout(syncActionFields, 0);
                    window.requestAnimationFrame(syncActionFields);
                }

                function resetShapeFields() {
                    currentObject = null;
                    shapeDataEl.value = '';
                    bboxXEl.value = '';
                    bboxYEl.value = '';
                    bboxWEl.value = '';
                    bboxHEl.value = '';
                }

                function resetPolygonDraft() {
                    polygonPoints = [];
                    if (polygonTemp) {
                        canvas.remove(polygonTemp);
                        polygonTemp = null;
                    }
                }

                function clearCanvasHotspots() {
                    if (!canvas) return;
                    resetPolygonDraft();
                    canvas.discardActiveObject();
                    canvas.getObjects().forEach(o => canvas.remove(o));
                    canvas.requestRenderAll();
                    resetShapeFields();
                }

                function resetEditorSelection(options = {}) {
                    if (!canvas) return;

                    const preserveCanvasObjects = options.preserveCanvasObjects !== false;
                    if (preserveCanvasObjects) {
                        resetPolygonDraft();
                        canvas.discardActiveObject();
                        canvas.requestRenderAll();
                    } else {
                        clearCanvasHotspots();
                    }

                    resetShapeFields();

                    if (options.clearHotspotId) {
                        hotspotIdEl.value = '';
                        setActiveHotspotListItem(null);
                        updateCurrentMedia();
                        clearFileInputs();
                        setFormMode(false);
                    }
                }

                function beginNewDrawing() {
                    resetEditorSelection({
                        preserveCanvasObjects: true,
                        clearHotspotId: true
                    });
                    setStatus('');
                }

                async function deleteSelected() {
                    if (!canvas) return;
                    const obj = canvas.getActiveObject();
                    if (!obj) return;

                    const hotspotId = obj.__hotspotData?.id || hotspotIdEl.value;

                    canvas.remove(obj);
                    canvas.discardActiveObject();
                    canvas.requestRenderAll();
                    resetShapeFields();

                    if (!hotspotId) {
                        setStatus('Selection removed.');
                        return;
                    }

                    const url = @json(url('/catalog/pdfs/' . $pdf->id . '/slicer')) + '/hotspots/' + hotspotId;
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        setStatus('Delete failed.', 'danger');
                        await loadHotspots();
                        return;
                    }

                    hotspotIdEl.value = '';
                    updateCurrentMedia();
                    clearFileInputs();
                    setFormMode(false);
                    setStatus('Deleted.', 'success');
                    await loadHotspots();
                    await renderThumbnailPreview();
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

                function detectShapeType(obj) {
                    if (!obj) return 'rectangle';
                    if (obj.__hotspotData?.shape_type) return obj.__hotspotData.shape_type;
                    if (obj.type === 'rect') return 'rectangle';
                    if (obj.type === 'path') return 'free';
                    return 'polygon';
                }

                function setCurrentObject(obj, shapeType, options = {}) {
                    currentObject = obj;
                    shapeTypeEl.value = shapeType;

                    if (Object.prototype.hasOwnProperty.call(options, 'hotspotId')) {
                        hotspotIdEl.value = options.hotspotId ? String(options.hotspotId) : '';
                    }

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

                    data.runtimeShape = buildRuntimeShape(shapeType, obj, n);
                    shapeDataEl.value = JSON.stringify(data);
                }

                function buildRuntimeShape(shapeType, obj, bbox) {
                    if (!canvas || !obj) {
                        return {
                            type: shapeType,
                            points: []
                        };
                    }

                    const width = canvas.getWidth() || 1;
                    const height = canvas.getHeight() || 1;
                    let points = [];

                    if (shapeType === 'rectangle') {
                        points = [{
                                x: bbox.x,
                                y: bbox.y
                            },
                            {
                                x: bbox.x + bbox.w,
                                y: bbox.y
                            },
                            {
                                x: bbox.x + bbox.w,
                                y: bbox.y + bbox.h
                            },
                            {
                                x: bbox.x,
                                y: bbox.y + bbox.h
                            }
                        ];
                    } else {
                        const transformed = typeof obj.getCoords === 'function' ? obj.getCoords() : [];
                        points = transformed.map(point => ({
                            x: Math.max(0, Math.min(1, point.x / width)),
                            y: Math.max(0, Math.min(1, point.y / height))
                        }));
                    }

                    return {
                        type: shapeType,
                        points,
                        bbox: {
                            x: bbox.x,
                            y: bbox.y,
                            w: bbox.w,
                            h: bbox.h
                        }
                    };
                }

                function setTool(tool) {
                    currentTool = tool;
                    if (!canvas) return;

                    canvas.isDrawingMode = tool === 'free';
                    canvas.selection = tool === 'select';
                    canvas.defaultCursor = tool === 'select' ? 'default' : 'crosshair';
                    if (canvas.isDrawingMode) {
                        canvas.freeDrawingBrush.width = 3;
                        canvas.freeDrawingBrush.color = 'rgba(0, 120, 255, 0.8)';
                    }

                    resetPolygonDraft();

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
                    resetEditorSelection({
                        preserveCanvasObjects: false,
                        clearHotspotId: true
                    });

                    const pid = currentPageId();
                    const p = pages.find(x => String(x.id) === String(pid));
                    if (p && p.image_path) {
                        await setBackgroundFromUrl(pageImageUrl(pid));
                        return;
                    }

                    await setBackgroundFromPdfPage(currentPageNumber());
                }

                function fillFormFromHotspot(h) {
                    hotspotIdEl.value = String(h.id);
                    setActionValue(h.action_type);

                    document.getElementById('isActive').checked = !!h.is_active;
                    document.getElementById('title').value = h.title || '';
                    document.getElementById('color').value = h.color || '';
                    document.getElementById('link').value = h.link || '';
                    document.getElementById('internalPage').value = h.internal_page_number || '';
                    document.getElementById('description').value = h.description || '';
                    document.getElementById('price').value = h.price || '';
                    popupVideoUrlEl.value = h.popup_video_url || '';
                    clearFileInputs();
                    updateCurrentMedia(h);
                    setActiveHotspotListItem(h.id);
                    setFormMode(true);
                }

                function getScaledHotspotData(hotspot) {
                    const data = hotspot.shape_data;
                    if (!data || !canvas) return null;

                    if (data.__meta && data.__meta.canvasWidth && data.__meta.canvasHeight) {
                        const sx = canvas.getWidth() / data.__meta.canvasWidth;
                        const sy = canvas.getHeight() / data.__meta.canvasHeight;
                        return scaleObjectData(data, sx, sy);
                    }

                    return data;
                }

                function enlivenShapeObject(shapeData) {
                    return new Promise((resolve) => {
                        fabric.util.enlivenObjects([shapeData], function(objects) {
                            resolve(objects && objects.length ? objects[0] : null);
                        });
                    });
                }

                function applyCanvasSelection(obj, hotspot = null) {
                    if (!obj || !canvas) return;

                    if (hotspot) {
                        fillFormFromHotspot(hotspot);
                    } else {
                        setActiveHotspotListItem(null);
                        updateCurrentMedia();
                        clearFileInputs();
                        setFormMode(false);
                    }

                    if (canvas.getActiveObject() !== obj) {
                        canvas.setActiveObject(obj);
                    }

                    setCurrentObject(obj, hotspot?.shape_type || detectShapeType(obj), {
                        hotspotId: hotspot?.id || obj.__hotspotData?.id || ''
                    });
                    canvas.requestRenderAll();
                    setStatus('');
                }

                async function renderHotspotsOnCanvas(hotspots, selectedHotspotId = null) {
                    if (!canvas) return;

                    clearCanvasHotspots();

                    let selectedObject = null;

                    for (const hotspot of hotspots) {
                        const scaledData = getScaledHotspotData(hotspot);
                        if (!scaledData) continue;

                        const obj = await enlivenShapeObject(scaledData);
                        if (!obj) continue;

                        obj.__hotspotData = hotspot;
                        obj.selectable = true;
                        obj.evented = true;
                        canvas.add(obj);

                        if (selectedHotspotId && String(hotspot.id) === String(selectedHotspotId)) {
                            selectedObject = obj;
                        }
                    }

                    if (selectedObject) {
                        applyCanvasSelection(selectedObject, selectedObject.__hotspotData);
                        return;
                    }

                    canvas.requestRenderAll();
                }

                async function loadHotspots(selectedHotspotId = null) {
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

                    await renderHotspotsOnCanvas(data, selectedHotspotId);

                    if (data.length === 0) {
                        hotspotList.innerHTML = '<div class="text-muted">No hotspots yet.</div>';
                        setActiveHotspotListItem(null);
                        return;
                    }

                    hotspotList.innerHTML = '';
                    for (const h of data) {
                        const item = document.createElement('a');
                        item.className = 'list-group-item list-group-item-action';
                        item.dataset.hotspotId = String(h.id);
                        item.textContent = (h.title ? h.title + ' — ' : '') + h.action_type;
                        item.addEventListener('click', () => {
                            loadHotspotIntoForm(h);
                        });
                        hotspotList.appendChild(item);
                    }

                    setActiveHotspotListItem(selectedHotspotId);
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
                    const existingObject = canvas?.getObjects().find(obj => String(obj.__hotspotData?.id || '') ===
                        String(h.id));

                    if (existingObject) {
                        applyCanvasSelection(existingObject, h);
                        return;
                    }

                    const scaledData = getScaledHotspotData(h);
                    if (!scaledData || !canvas) return;

                    enlivenShapeObject(scaledData).then((obj) => {
                        if (!obj) return;
                        obj.__hotspotData = h;
                        canvas.add(obj);
                        applyCanvasSelection(obj, h);
                    });
                }

                async function saveHotspot() {
                    if (!currentObject || !backgroundLoaded) {
                        setStatus('Draw or select a hotspot first.', 'danger');
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
                    saveButton.disabled = true;

                    try {
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
                                const firstError = Object.values(j.errors || {})
                                    .flat()
                                    .find(Boolean);
                                message = firstError || j.message || message;
                            } catch (e) {}
                            setStatus(message, 'danger');
                            return;
                        }

                        const json = await res.json();
                        const saved = json.data;
                        hotspotIdEl.value = String(saved.id);
                        updateCurrentMedia(saved);
                        clearFileInputs();

                        setStatus('Saved.', 'success');
                        await loadHotspots(saved.id);
                        await renderThumbnailPreview();
                    } finally {
                        saveButton.disabled = false;
                    }
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

                        if (currentTool === 'select') {
                            return;
                        }

                        if (o.target) {
                            applyCanvasSelection(o.target, o.target.__hotspotData || null);
                            return;
                        }

                        if (currentTool === 'rectangle') {
                            beginNewDrawing();
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

                            canvas.add(rect);
                            canvas.setActiveObject(rect);
                        }

                        if (currentTool === 'polygon') {
                            if (polygonPoints.length === 0) {
                                beginNewDrawing();
                            }

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

                            canvas.add(polygonTemp);
                            canvas.requestRenderAll();
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
                        canvas.requestRenderAll();
                    });

                    canvas.on('mouse:up', function() {
                        if (currentTool === 'rectangle' && rect) {
                            isDown = false;
                            setCurrentObject(rect, 'rectangle', {
                                hotspotId: ''
                            });
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
                        canvas.add(poly);
                        canvas.setActiveObject(poly);
                        canvas.requestRenderAll();
                        setCurrentObject(poly, 'polygon', {
                            hotspotId: ''
                        });
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

                        canvas.setActiveObject(path);
                        canvas.requestRenderAll();
                        setCurrentObject(path, 'free', {
                            hotspotId: ''
                        });
                        setFormMode(false);
                        updateCurrentMedia();
                        clearFileInputs();
                        setStatus('');
                    });

                    canvas.on('object:modified', function(event) {
                        const obj = event.target;
                        if (!obj) return;

                        const hotspot = obj.__hotspotData || null;
                        setCurrentObject(obj, hotspot?.shape_type || detectShapeType(obj), {
                            hotspotId: hotspot?.id || ''
                        });
                        setActiveHotspotListItem(hotspot?.id || null);
                        setStatus('Shape updated. Save hotspot to keep changes.', 'warning');
                    });

                    function syncSelectionFromCanvas(event) {
                        const obj = event.selected && event.selected[0] ? event.selected[0] : null;
                        if (!obj) return;

                        if (obj.__hotspotData) {
                            applyCanvasSelection(obj, obj.__hotspotData);
                            return;
                        }

                        setCurrentObject(obj, detectShapeType(obj), {
                            hotspotId: ''
                        });
                    }

                    canvas.on('selection:created', syncSelectionFromCanvas);
                    canvas.on('selection:updated', syncSelectionFromCanvas);
                    canvas.on('selection:cleared', function() {
                        resetShapeFields();
                        setActiveHotspotListItem(null);
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
                    setActionValue(actionTypeEl.value);
                    resetEditorSelection({
                        preserveCanvasObjects: true,
                        clearHotspotId: true
                    });
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
                bindActionTypeEvents();

                document.getElementById('toolRect')?.addEventListener('click', () => {
                    setTool('rectangle');
                    shapeTypeEl.value = 'rectangle';
                });
                document.getElementById('toolSelect')?.addEventListener('click', () => {
                    setTool('select');
                });
                document.getElementById('toolPoly')?.addEventListener('click', () => {
                    setTool('polygon');
                    shapeTypeEl.value = 'polygon';
                });
                document.getElementById('toolFree')?.addEventListener('click', () => {
                    setTool('free');
                    shapeTypeEl.value = 'free';
                });

                document.getElementById('btnClear')?.addEventListener('click', () => {
                    resetEditorSelection({
                        preserveCanvasObjects: true,
                        clearHotspotId: true
                    });
                });
                document.getElementById('btnDeleteSelected')?.addEventListener('click', deleteSelected);
                document.getElementById('btnNew')?.addEventListener('click', resetFormForNew);
                document.getElementById('btnInitPages')?.addEventListener('click', initPagesClientSide);
                document.getElementById('btnRefreshPreview')?.addEventListener('click', renderThumbnailPreview);

                formEl?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveHotspot();
                });

                // Boot
                syncActionFields();
                setupCanvas();
                if (pageSelect && pages && pages.length > 0) {
                    setTool('rectangle');
                    setFormMode(false);
                    updateCurrentMedia();
                    onPageChanged();
                    pageSelect.addEventListener('change', onPageChanged);
                }
            })();
        </script>
    @endpush

</x-default-layout>
