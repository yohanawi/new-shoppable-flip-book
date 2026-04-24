<x-default-layout>

    @section('title')
        Share Preview Editor
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.share-preview.edit', $pdf) }}
    @endsection

    <style>
        .share-studio-stage {
            position: relative;
            min-height: 640px;
            border-radius: 28px;
            overflow: hidden;
            background: linear-gradient(160deg, var(--studio-bg, #0F172A) 0%, var(--studio-bg, #0F172A) 100%);
            box-shadow: 0 30px 70px rgba(15, 23, 42, 0.24);
        }

        .share-studio-stage::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.25), rgba(2, 6, 23, 0.55));
            pointer-events: none;
            z-index: 1;
        }

        .share-studio-media,
        .share-studio-media img,
        .share-studio-media video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .share-studio-media img,
        .share-studio-media video {
            object-fit: cover;
        }

        .share-studio-toolbar {
            position: absolute;
            inset: 0 0 auto 0;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            z-index: 3;
            color: #F8FAFC;
            background: var(--studio-toolbar-bg, #020617);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .share-studio-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .share-studio-book {
            position: absolute;
            inset: 118px 54px 42px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.18);
            z-index: 2;
            padding: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .share-studio-book-card {
            width: min(540px, 100%);
            min-height: 360px;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(241, 245, 249, 0.94));
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.24);
            overflow: hidden;
        }

        .share-studio-book-card-header {
            padding: 22px 24px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .share-studio-book-card-body {
            padding: 24px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .share-studio-book-tile {
            height: 128px;
            border-radius: 18px;
            background: linear-gradient(135deg, #E2E8F0, #CBD5E1);
            border: 1px dashed rgba(100, 116, 139, 0.55);
        }

        .share-studio-brand {
            position: absolute;
            z-index: 4;
            display: none;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.66);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(14px);
            color: #F8FAFC;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.35);
        }

        .share-studio-brand img {
            max-width: 100%;
            display: block;
        }

        .share-studio-brand-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }

        .share-studio-background-panel {
            display: none;
        }

        .share-studio-background-panel.is-active {
            display: block;
        }

        .share-studio-type-card {
            cursor: pointer;
            border: 1px solid var(--bs-gray-300);
            border-radius: 18px;
            padding: 18px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
            height: 100%;
        }

        .share-studio-type-card:hover,
        .share-studio-type-card.is-active {
            border-color: var(--bs-primary);
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .share-studio-range-value {
            min-width: 54px;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .share-studio-stage {
                min-height: 520px;
            }

            .share-studio-book {
                inset: 110px 18px 18px;
            }

            .share-studio-book-card-body {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-information-5 fs-2 text-danger mt-1"></i>
            <div>
                <div class="fw-bold mb-2">Please fix the highlighted share preview settings.</div>
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>
                <div class="fw-bold mb-1">Share preview saved</div>
                <div>{{ session('success') }}</div>
            </div>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-3 mb-8 justify-content-between">
        <a href="{{ route('catalog.pdfs.share-preview.index') }}" class="btn btn-light border">
            <i class="ki-outline ki-arrow-left fs-2"></i> Back to Studio
        </a>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="{{ $shareUrl }}" class="btn btn-light-success" target="_blank" rel="noopener">
                Open Shared Preview
            </a>
            <button type="button" class="btn btn-light-primary" onclick="copyToClipboard('{{ $shareUrl }}')">
                Copy Share Link
            </button>
        </div>
    </div>

    <div class="row g-8">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm sticky-lg-top" style="top: 110px;">
                <div class="card-header border-0 pt-8 pb-0">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold text-gray-900 mb-1">Live composition preview</h3>
                        <div class="text-muted">This mockup mirrors the shared experience with your current background,
                            logo, title, and placement settings.</div>
                    </div>
                </div>
                <div class="card-body pt-6">
                    <div id="shareStudioStage" class="share-studio-stage"
                        style="--studio-bg: {{ $shareAppearance['backgroundColor'] }}; --studio-toolbar-bg: {{ $shareAppearance['toolbarBackgroundColor'] }};">
                        <div class="share-studio-media">
                            <img id="shareStudioBackgroundImage" alt="Background preview" style="display:none;">
                            <video id="shareStudioBackgroundVideo" muted autoplay loop playsinline
                                style="display:none;"></video>
                        </div>

                        <div class="share-studio-toolbar" id="shareStudioToolbar">
                            <div>
                                <div class="text-white fw-bold fs-2 mb-1">{{ $pdf->title }}</div>
                                <div class="text-white opacity-75">Shared PDF viewer</div>
                            </div>
                            <span class="share-studio-pill"
                                id="shareStudioModePill">{{ ucfirst($shareAppearance['backgroundType']) }}
                                background</span>
                        </div>

                        <div class="share-studio-brand" id="shareStudioBrand">
                            <div id="shareStudioLogoWrap" style="display:none;">
                                <img id="shareStudioLogo" alt="Logo preview">
                            </div>
                            <div id="shareStudioBrandTitle" class="share-studio-brand-title"></div>
                        </div>

                        <div class="share-studio-book">
                            <div class="share-studio-book-card">
                                <div class="share-studio-book-card-header">
                                    <div class="text-gray-900 fw-bold fs-3">Reader view</div>
                                    <div class="text-muted fs-7">The share page still loads the actual PDF and page
                                        interactions. This studio controls the branded shell around it.</div>
                                </div>
                                <div class="share-studio-book-card-body">
                                    <div class="share-studio-book-tile"></div>
                                    <div class="share-studio-book-tile"></div>
                                    <div class="share-studio-book-tile"></div>
                                    <div class="share-studio-book-tile"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <form action="{{ route('catalog.pdfs.share-preview.update', $pdf) }}" method="POST"
                enctype="multipart/form-data" class="d-flex flex-column gap-8">
                @csrf

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="fw-bold text-gray-900 mb-1">Background</h3>
                            <div class="text-muted">Choose the first thing a viewer sees before the PDF loads.</div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-4 mb-7" id="backgroundTypeCards">
                            @foreach ($backgroundTypeOptions as $value => $label)
                                <div class="col-md-4">
                                    <label
                                        class="share-studio-type-card {{ old('background_type', $setting->background_type) === $value ? 'is-active' : '' }} w-100">
                                        <div class="d-flex align-items-start gap-3">
                                            <input class="form-check-input mt-1" type="radio" name="background_type"
                                                value="{{ $value }}"
                                                {{ old('background_type', $setting->background_type) === $value ? 'checked' : '' }}>
                                            <div>
                                                <div class="fw-bold text-gray-900 mb-1">{{ $label }}</div>
                                                <div class="text-muted fs-7">
                                                    @if ($value === \App\Models\CatalogPdfSharePreviewSetting::BACKGROUND_COLOR)
                                                        Use a solid brand color with the built-in ambient overlays.
                                                    @elseif ($value === \App\Models\CatalogPdfSharePreviewSetting::BACKGROUND_IMAGE)
                                                        Place the PDF experience on top of a custom image or poster.
                                                    @else
                                                        Use a looping MP4 or WebM clip behind the shared viewer shell.
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-6">
                            <div class="col-12">
                                <label class="form-label fw-bold text-gray-900">Base background color</label>
                                <div class="d-flex align-items-center gap-4 flex-wrap">
                                    <input type="color" class="form-control form-control-color"
                                        id="backgroundColorPicker" name="background_color"
                                        value="{{ old('background_color', $setting->background_color) }}"
                                        title="Choose background color">
                                    <input type="text" class="form-control form-control-solid mw-175px"
                                        id="backgroundColorText"
                                        value="{{ old('background_color', $setting->background_color) }}"
                                        maxlength="7">
                                </div>
                                <div class="text-muted fs-7 mt-2">This color is always used as the fallback layer and
                                    keeps text readable even when media is loading.</div>
                            </div>

                            <div class="col-12 share-studio-background-panel" data-background-panel="image">
                                <label class="form-label fw-bold text-gray-900">Background image</label>
                                <input type="file" class="form-control form-control-solid" name="background_image"
                                    id="backgroundImageInput" accept="image/*">
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                                    @if ($shareAppearance['backgroundImageUrl'])
                                        <a href="{{ $shareAppearance['backgroundImageUrl'] }}"
                                            class="btn btn-sm btn-light" target="_blank" rel="noopener">View current
                                            image</a>
                                    @endif
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox"
                                            name="remove_background_image" value="1" id="removeBackgroundImage">
                                        <span class="form-check-label text-gray-700">Remove current image</span>
                                    </label>
                                </div>
                                <div class="text-muted fs-7 mt-2">JPG, PNG, WEBP or SVG. Maximum 10 MB.</div>
                            </div>

                            <div class="col-12 share-studio-background-panel" data-background-panel="video">
                                <label class="form-label fw-bold text-gray-900">Background video</label>
                                <input type="file" class="form-control form-control-solid" name="background_video"
                                    id="backgroundVideoInput" accept="video/mp4,video/webm">
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                                    @if ($shareAppearance['backgroundVideoUrl'])
                                        <a href="{{ $shareAppearance['backgroundVideoUrl'] }}"
                                            class="btn btn-sm btn-light" target="_blank" rel="noopener">Play current
                                            video</a>
                                    @endif
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox"
                                            name="remove_background_video" value="1" id="removeBackgroundVideo">
                                        <span class="form-check-label text-gray-700">Remove current video</span>
                                    </label>
                                </div>
                                <div class="text-muted fs-7 mt-2">MP4 or WebM. Maximum 50 MB. The shared page autoplays
                                    it muted in a loop.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="fw-bold text-gray-900 mb-1">Branding</h3>
                            <div class="text-muted">Add a logo lockup and optional title to the shared shell.</div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <label class="form-label fw-bold text-gray-900">Logo</label>
                                <input type="file" class="form-control form-control-solid" name="logo"
                                    id="logoInput" accept="image/*">
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                                    @if ($shareAppearance['logoUrl'])
                                        <a href="{{ $shareAppearance['logoUrl'] }}" class="btn btn-sm btn-light"
                                            target="_blank" rel="noopener">View current logo</a>
                                    @endif
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="remove_logo"
                                            value="1" id="removeLogo">
                                        <span class="form-check-label text-gray-700">Remove current logo</span>
                                    </label>
                                </div>
                                <div class="text-muted fs-7 mt-2">Transparent PNG or SVG works best.</div>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label fw-bold text-gray-900">Logo title</label>
                                <input type="text" class="form-control form-control-solid" name="logo_title"
                                    id="logoTitleInput" maxlength="120"
                                    value="{{ old('logo_title', $setting->logo_title) }}"
                                    placeholder="Example: Spring 2026 Collection">
                                <div class="text-muted fs-7 mt-2">Use this for a campaign title, brand descriptor, or
                                    product line name next to the logo.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="fw-bold text-gray-900 mb-1">Toolbar</h3>
                            <div class="text-muted">Control the top bar that shows the PDF title and page status on the
                                shared experience.</div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-6 align-items-end">
                            <div class="col-lg-7">
                                <label class="form-label fw-bold text-gray-900">Toolbar background color</label>
                                <div class="d-flex align-items-center gap-4 flex-wrap">
                                    <input type="color" class="form-control form-control-color"
                                        id="toolbarColorPicker" name="toolbar_background_color"
                                        value="{{ old('toolbar_background_color', $setting->toolbar_background_color ?: \App\Models\CatalogPdfSharePreviewSetting::defaultToolbarBackgroundColor()) }}"
                                        title="Choose toolbar color">
                                    <input type="text" class="form-control form-control-solid mw-175px"
                                        id="toolbarColorText"
                                        value="{{ old('toolbar_background_color', $setting->toolbar_background_color ?: \App\Models\CatalogPdfSharePreviewSetting::defaultToolbarBackgroundColor()) }}"
                                        maxlength="7">
                                </div>
                                <div class="text-muted fs-7 mt-2">This color is applied directly to the share toolbar
                                    so the title and page counter sit on your chosen brand color.</div>
                            </div>
                            <div class="col-lg-5">
                                <input type="hidden" name="toolbar_is_visible" value="0">
                                <label class="form-check form-check-custom form-check-solid mt-8">
                                    <input class="form-check-input" type="checkbox" name="toolbar_is_visible"
                                        value="1" id="toolbarVisibleInput"
                                        {{ old('toolbar_is_visible', $setting->toolbar_is_visible ?? true) ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-gray-900">Show toolbar on shared
                                        page</span>
                                </label>
                                <div class="text-muted fs-7 mt-2">Uncheck this to hide the toolbar entirely and show
                                    only the branded viewer shell.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-8">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="fw-bold text-gray-900 mb-1">Logo Position</h3>
                            <div class="text-muted">Move the lockup anywhere on the share shell and resize it to fit
                                the composition.</div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-6">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-gray-900 mb-0">Horizontal position</label>
                                    <span class="badge badge-light-primary share-studio-range-value"
                                        id="logoPositionXValue">{{ old('logo_position_x', $setting->logo_position_x) }}%</span>
                                </div>
                                <input type="range" class="form-range" min="0" max="100"
                                    step="1" name="logo_position_x" id="logoPositionXInput"
                                    value="{{ old('logo_position_x', $setting->logo_position_x) }}">
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-gray-900 mb-0">Vertical position</label>
                                    <span class="badge badge-light-primary share-studio-range-value"
                                        id="logoPositionYValue">{{ old('logo_position_y', $setting->logo_position_y) }}%</span>
                                </div>
                                <input type="range" class="form-range" min="0" max="100"
                                    step="1" name="logo_position_y" id="logoPositionYInput"
                                    value="{{ old('logo_position_y', $setting->logo_position_y) }}">
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-gray-900 mb-0">Logo width</label>
                                    <span class="badge badge-light-primary share-studio-range-value"
                                        id="logoWidthValue">{{ old('logo_width', $setting->logo_width) }}px</span>
                                </div>
                                <input type="range" class="form-range" min="60" max="320"
                                    step="2" name="logo_width" id="logoWidthInput"
                                    value="{{ old('logo_width', $setting->logo_width) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 flex-wrap">
                    <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-light">Back to PDF</a>
                    <button type="submit" class="btn btn-primary">Save Share Preview</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const initialAppearance = @json($shareAppearance);
                const stage = document.getElementById('shareStudioStage');
                const backgroundImageEl = document.getElementById('shareStudioBackgroundImage');
                const backgroundVideoEl = document.getElementById('shareStudioBackgroundVideo');
                const toolbarEl = document.getElementById('shareStudioToolbar');
                const brandEl = document.getElementById('shareStudioBrand');
                const brandTitleEl = document.getElementById('shareStudioBrandTitle');
                const logoWrapEl = document.getElementById('shareStudioLogoWrap');
                const logoEl = document.getElementById('shareStudioLogo');
                const modePillEl = document.getElementById('shareStudioModePill');
                const colorPickerEl = document.getElementById('backgroundColorPicker');
                const colorTextEl = document.getElementById('backgroundColorText');
                const toolbarColorPickerEl = document.getElementById('toolbarColorPicker');
                const toolbarColorTextEl = document.getElementById('toolbarColorText');
                const toolbarVisibleInputEl = document.getElementById('toolbarVisibleInput');
                const backgroundImageInputEl = document.getElementById('backgroundImageInput');
                const backgroundVideoInputEl = document.getElementById('backgroundVideoInput');
                const logoInputEl = document.getElementById('logoInput');
                const removeBackgroundImageEl = document.getElementById('removeBackgroundImage');
                const removeBackgroundVideoEl = document.getElementById('removeBackgroundVideo');
                const removeLogoEl = document.getElementById('removeLogo');
                const logoTitleInputEl = document.getElementById('logoTitleInput');
                const logoPositionXInputEl = document.getElementById('logoPositionXInput');
                const logoPositionYInputEl = document.getElementById('logoPositionYInput');
                const logoWidthInputEl = document.getElementById('logoWidthInput');
                const logoPositionXValueEl = document.getElementById('logoPositionXValue');
                const logoPositionYValueEl = document.getElementById('logoPositionYValue');
                const logoWidthValueEl = document.getElementById('logoWidthValue');
                const previewUrls = {
                    backgroundImage: null,
                    backgroundVideo: null,
                    logo: null,
                };

                function selectedBackgroundType() {
                    return document.querySelector('input[name="background_type"]:checked')?.value || initialAppearance
                        .backgroundType;
                }

                function backgroundLabel(type) {
                    switch (type) {
                        case 'image':
                            return 'Image background';
                        case 'video':
                            return 'Video background';
                        default:
                            return 'Color background';
                    }
                }

                function previewUrl(currentKey, fallback) {
                    return previewUrls[currentKey] || fallback || null;
                }

                function revokePreviewUrl(key) {
                    if (previewUrls[key]) {
                        URL.revokeObjectURL(previewUrls[key]);
                        previewUrls[key] = null;
                    }
                }

                function syncBackgroundPanels() {
                    const type = selectedBackgroundType();
                    document.querySelectorAll('[data-background-panel]').forEach((panel) => {
                        panel.classList.toggle('is-active', panel.dataset.backgroundPanel === type);
                    });

                    document.querySelectorAll('#backgroundTypeCards .share-studio-type-card').forEach((card) => {
                        const radio = card.querySelector('input[type="radio"]');
                        card.classList.toggle('is-active', radio?.checked === true);
                    });
                }

                function syncRangeLabels() {
                    logoPositionXValueEl.textContent = logoPositionXInputEl.value + '%';
                    logoPositionYValueEl.textContent = logoPositionYInputEl.value + '%';
                    logoWidthValueEl.textContent = logoWidthInputEl.value + 'px';
                }

                function updatePreview() {
                    const type = selectedBackgroundType();
                    const backgroundColor = colorPickerEl.value || '#0F172A';
                    const toolbarColor = toolbarColorPickerEl.value || '#020617';
                    const imageUrl = removeBackgroundImageEl.checked ? null : previewUrl('backgroundImage',
                        initialAppearance.backgroundImageUrl);
                    const videoUrl = removeBackgroundVideoEl.checked ? null : previewUrl('backgroundVideo',
                        initialAppearance.backgroundVideoUrl);
                    const logoUrl = removeLogoEl.checked ? null : previewUrl('logo', initialAppearance.logoUrl);
                    const title = logoTitleInputEl.value.trim();

                    stage.style.setProperty('--studio-bg', backgroundColor);
                    stage.style.setProperty('--studio-toolbar-bg', toolbarColor);
                    modePillEl.textContent = backgroundLabel(type);
                    toolbarEl.style.display = toolbarVisibleInputEl.checked ? 'flex' : 'none';

                    backgroundImageEl.style.display = type === 'image' && imageUrl ? '' : 'none';
                    if (imageUrl) {
                        backgroundImageEl.src = imageUrl;
                    } else {
                        backgroundImageEl.removeAttribute('src');
                    }

                    backgroundVideoEl.style.display = type === 'video' && videoUrl ? '' : 'none';
                    if (videoUrl) {
                        if (backgroundVideoEl.getAttribute('src') !== videoUrl) {
                            backgroundVideoEl.setAttribute('src', videoUrl);
                            backgroundVideoEl.load();
                        }

                        backgroundVideoEl.play().catch(() => {});
                    } else {
                        backgroundVideoEl.pause();
                        backgroundVideoEl.removeAttribute('src');
                        backgroundVideoEl.load();
                    }

                    logoWrapEl.style.display = logoUrl ? '' : 'none';
                    if (logoUrl) {
                        logoEl.src = logoUrl;
                        logoEl.style.width = logoWidthInputEl.value + 'px';
                    } else {
                        logoEl.removeAttribute('src');
                    }

                    brandTitleEl.textContent = title;
                    brandTitleEl.style.display = title ? '' : 'none';
                    brandEl.style.display = logoUrl || title ? 'flex' : 'none';
                    brandEl.style.left = logoPositionXInputEl.value + '%';
                    brandEl.style.top = logoPositionYInputEl.value + '%';
                }

                function readPreviewFile(input, key) {
                    const file = input.files?.[0];
                    revokePreviewUrl(key);

                    if (!file) {
                        updatePreview();
                        return;
                    }

                    previewUrls[key] = URL.createObjectURL(file);
                    updatePreview();
                }

                colorPickerEl.addEventListener('input', () => {
                    colorTextEl.value = colorPickerEl.value.toUpperCase();
                    updatePreview();
                });

                colorTextEl.addEventListener('input', () => {
                    const value = colorTextEl.value.trim();
                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        colorPickerEl.value = value;
                        updatePreview();
                    }
                });

                toolbarColorPickerEl.addEventListener('input', () => {
                    toolbarColorTextEl.value = toolbarColorPickerEl.value.toUpperCase();
                    updatePreview();
                });

                toolbarColorTextEl.addEventListener('input', () => {
                    const value = toolbarColorTextEl.value.trim();
                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        toolbarColorPickerEl.value = value;
                        updatePreview();
                    }
                });

                document.querySelectorAll('input[name="background_type"]').forEach((input) => {
                    input.addEventListener('change', () => {
                        syncBackgroundPanels();
                        updatePreview();
                    });
                });

                backgroundImageInputEl.addEventListener('change', () => readPreviewFile(backgroundImageInputEl,
                    'backgroundImage'));
                backgroundVideoInputEl.addEventListener('change', () => readPreviewFile(backgroundVideoInputEl,
                    'backgroundVideo'));
                logoInputEl.addEventListener('change', () => readPreviewFile(logoInputEl, 'logo'));

                [removeBackgroundImageEl, removeBackgroundVideoEl, removeLogoEl, toolbarVisibleInputEl, logoTitleInputEl,
                    logoPositionXInputEl,
                    logoPositionYInputEl, logoWidthInputEl
                ].forEach((element) => {
                    element.addEventListener('input', () => {
                        syncRangeLabels();
                        updatePreview();
                    });
                    element.addEventListener('change', () => {
                        syncRangeLabels();
                        updatePreview();
                    });
                });

                syncBackgroundPanels();
                syncRangeLabels();
                updatePreview();

                window.copyToClipboard = function(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (window.toastr) {
                            toastr.success('Link copied to clipboard.');
                            return;
                        }

                        alert('Link copied to clipboard.');
                    }).catch((error) => {
                        console.error(error);
                    });
                };
            })();
        </script>
    @endpush

</x-default-layout>
