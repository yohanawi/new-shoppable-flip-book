<x-default-layout>

    @section('title')
        Catalog PDFs
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('catalog.pdfs.index') }}
    @endsection

    @php($hasFilters = filled($filters['search'] ?? null) || filled($filters['visibility'] ?? null) || filled($filters['template_type'] ?? null))
    @php($showingFrom = $pdfs->firstItem() ?? 0)
    @php($showingTo = $pdfs->lastItem() ?? 0)

    <style>
        .catalog-index-shell {
            --catalog-ink: #123047;
            --catalog-muted: #607385;
            --catalog-paper: rgba(255, 255, 255, 0.86);
            --catalog-border: rgba(18, 48, 71, 0.08);
            --catalog-orange: #ff7a18;
            --catalog-amber: #ffb84d;
            --catalog-blue: #1d4ed8;
            --catalog-teal: #0f9f9a;
            position: relative;
        }

        .catalog-surface {
            background:
                radial-gradient(circle at top left, rgba(255, 184, 77, 0.16), transparent 30%),
                radial-gradient(circle at top right, rgba(15, 159, 154, 0.14), transparent 28%),
                linear-gradient(180deg, #fff9ef 0%, #f7fbff 48%, #eef8f4 100%);
            border-radius: 32px;
            padding: 24px;
        }

        .catalog-hero-card {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 30px;
            background:
                radial-gradient(circle at 10% 15%, rgba(255, 184, 77, 0.30), transparent 22%),
                radial-gradient(circle at 88% 12%, rgba(23, 162, 184, 0.28), transparent 18%),
                linear-gradient(135deg, #0f172a 0%, #133b5c 46%, #0f766e 100%);
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.18);
        }

        .catalog-hero-card::after {
            content: '';
            position: absolute;
            inset: auto -100px -120px auto;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            filter: blur(4px);
        }

        .catalog-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .catalog-kpi {
            border-radius: 24px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
            color: #ffffff;
            align-items: center;
        }

        .catalog-kpi-label {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.72;
            margin-bottom: 6px;
        }

        .catalog-kpi-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
        }

        .catalog-panel {
            border: 1px solid var(--catalog-border);
            border-radius: 28px;
            background: var(--catalog-paper);
            box-shadow: 0 18px 40px rgba(18, 48, 71, 0.08);
            backdrop-filter: blur(12px);
        }

        .catalog-panel-title {
            color: var(--catalog-ink);
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 0.35rem;
        }

        .catalog-panel-subtitle {
            color: var(--catalog-muted);
            font-size: 0.95rem;
        }

        .catalog-stat-card {
            height: 100%;
            border: 1px solid var(--catalog-border);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.76);
            padding: 22px;
        }

        .catalog-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .catalog-stat-icon.is-orange {
            background: rgba(255, 122, 24, 0.14);
            color: var(--catalog-orange);
        }

        .catalog-stat-icon.is-blue {
            background: rgba(29, 78, 216, 0.12);
            color: var(--catalog-blue);
        }

        .catalog-stat-icon.is-teal {
            background: rgba(15, 159, 154, 0.12);
            color: var(--catalog-teal);
        }

        .catalog-stat-icon.is-amber {
            background: rgba(255, 184, 77, 0.16);
            color: #c77c11;
        }

        .catalog-results-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
        }

        .catalog-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .catalog-chip {
            border-radius: 999px;
            background: rgba(18, 48, 71, 0.06);
            color: var(--catalog-ink);
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .catalog-card {
            border: 1px solid var(--catalog-border);
            border-radius: 28px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 251, 255, 0.88));
            box-shadow: 0 18px 38px rgba(18, 48, 71, 0.08);
            overflow: hidden;
            height: 100%;
        }

        .catalog-card-topbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .catalog-card-metadata {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0 22px;
        }

        .catalog-card-meta {
            border-radius: 18px;
            background: rgba(18, 48, 71, 0.04);
            padding: 14px;
        }

        .catalog-card-focus {
            border-radius: 22px;
            padding: 16px 18px;
            background: linear-gradient(135deg, rgba(255, 122, 24, 0.10), rgba(15, 159, 154, 0.10));
            border: 1px solid rgba(18, 48, 71, 0.06);
            margin-bottom: 20px;
        }

        .catalog-tool-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .catalog-tool-link {
            border-radius: 18px;
            padding: 14px 16px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
            display: block;
        }

        .catalog-tool-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(18, 48, 71, 0.12);
        }

        .catalog-tool-link.is-page {
            background: rgba(29, 78, 216, 0.10);
            color: var(--catalog-blue);
        }

        .catalog-tool-link.is-flip {
            background: rgba(15, 159, 154, 0.10);
            color: var(--catalog-teal);
        }

        .catalog-tool-link.is-slicer {
            background: rgba(255, 122, 24, 0.12);
            color: #c8610d;
        }

        .catalog-empty-state {
            border: 1px dashed rgba(18, 48, 71, 0.14);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.72);
            padding: 48px 32px;
            text-align: center;
        }

        .catalog-admin-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(18, 48, 71, 0.06);
            color: var(--catalog-muted);
            font-size: 0.8rem;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .catalog-surface {
                border-radius: 24px;
                padding: 16px;
            }

            .catalog-kpi-grid,
            .catalog-card-metadata,
            .catalog-tool-grid {
                grid-template-columns: 1fr;
            }

            .catalog-results-head,
            .catalog-card-topbar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <div class="catalog-index-shell">
        <div class="catalog-surface">
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-start gap-3 mb-8 border-0 shadow-sm">
                    <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
                    <div>
                        <div class="fw-bold mb-1">Catalog updated</div>
                        <div>{{ session('success') }}</div>
                    </div>
                </div>
            @endif

            <div class="catalog-hero-card mb-8">
                <div class="card-body p-10 p-lg-14 position-relative">
                    <div class="row g-8 align-items-center">
                        <div class="col-xl-3">
                            <span class="badge badge-light-warning text-dark mb-4">Creative catalog workspace</span>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('catalog.pdfs.create') }}"
                                    class="btn btn-warning btn-lg text-dark fw-bold">
                                    <i class="ki-outline ki-plus fs-3 me-2"></i>
                                    Upload New PDF
                                </a>
                                @if ($hasFilters)
                                    <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light btn-lg">
                                        <i class="ki-outline ki-cross-circle fs-3 me-2"></i>
                                        Clear filters
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-xl-9">
                            <div class="catalog-kpi-grid">
                                <div class="catalog-kpi text-center">
                                    <div class="catalog-kpi-label">Visible in workspace</div>
                                    <div class="catalog-kpi-value">{{ number_format($stats['total']) }}</div>
                                </div>
                                <div class="catalog-kpi text-center">
                                    <div class="catalog-kpi-label">Ready for sharing</div>
                                    <div class="catalog-kpi-value">{{ number_format($stats['public']) }}</div>
                                </div>
                                <div class="catalog-kpi text-center">
                                    <div class="catalog-kpi-label">Private drafts</div>
                                    <div class="catalog-kpi-value">{{ number_format($stats['private']) }}</div>
                                </div>
                                <div class="catalog-kpi text-center">
                                    <div class="catalog-kpi-label">Upload-first files</div>
                                    <div class="catalog-kpi-value">{{ number_format($stats['uploaded']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="catalog-panel mb-8">
                <div class="card-body p-8 p-lg-10">
                    <div class="catalog-results-head">
                        <div>
                            <div class="catalog-panel-title">Find the right PDF fast</div>
                            <div class="catalog-panel-subtitle">
                                Filter by visibility or workflow, search across titles and file names, and keep those
                                selections while paging.
                            </div>
                        </div>
                        <div class="catalog-chip">{{ number_format($stats['total']) }} matching
                            result{{ $stats['total'] === 1 ? '' : 's' }}</div>
                    </div>

                    <form action="{{ route('catalog.pdfs.index') }}" method="GET" class="row g-4 align-items-end">
                        <div class="col-xl-4">
                            <label class="form-label fw-bold text-gray-900">Search PDFs</label>
                            <input type="text" name="search" value="{{ $filters['search'] }}"
                                class="form-control form-control-solid" placeholder="Title, file name, or description">
                        </div>
                        <div class="col-md-4 col-xl-2">
                            <label class="form-label fw-bold text-gray-900">Visibility</label>
                            <select name="visibility" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All visibility</option>
                                @foreach ($visibilityOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ ($filters['visibility'] ?? null) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-xl-3">
                            <label class="form-label fw-bold text-gray-900">Workflow focus</label>
                            <select name="template_type" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                <option value="">All workflows</option>
                                @foreach ($templateTypeOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ ($filters['template_type'] ?? null) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-xl-3">
                            <label class="form-label fw-bold text-gray-900">Sort</label>
                            <select name="sort" class="form-select form-select-solid" data-control="select2"
                                data-hide-search="true">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ ($filters['sort'] ?? 'latest') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-12">
                            <div class="d-flex flex-wrap gap-3 pt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ki-outline ki-magnifier fs-3 me-2"></i>
                                    Apply filters
                                </button>
                                <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </form>

                    @if ($hasFilters)
                        <div class="catalog-chip-row mt-5">
                            @if (filled($filters['search']))
                                <span class="catalog-chip">Search: {{ $filters['search'] }}</span>
                            @endif
                            @if (filled($filters['visibility']))
                                <span class="catalog-chip">Visibility:
                                    {{ $visibilityOptions[$filters['visibility']] ?? $filters['visibility'] }}</span>
                            @endif
                            @if (filled($filters['template_type']))
                                <span class="catalog-chip">Workflow:
                                    {{ $templateTypeOptions[$filters['template_type']] ?? $filters['template_type'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @if ($pdfs->total() === 0)
                <div class="catalog-empty-state">
                    <i class="ki-outline ki-file-deleted fs-5x text-muted mb-5"></i>
                    <h2 class="fw-bold text-gray-900 mb-3">
                        {{ $hasFilters ? 'No PDFs match these filters' : 'No PDFs yet' }}
                    </h2>
                    <div class="text-muted fs-6 mb-6 mx-auto" style="max-width: 620px;">
                        @if ($hasFilters)
                            Try clearing one of the filters or use a broader search term. The search now checks title,
                            file name, and description.
                        @else
                            Upload your first PDF and it will appear here with direct access to Page Management,
                            Flip Physics, Slicer, download, and share actions.
                        @endif
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        @if ($hasFilters)
                            <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light">Clear filters</a>
                        @endif
                        <a href="{{ route('catalog.pdfs.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-3 me-2"></i>
                            Upload PDF
                        </a>
                    </div>
                </div>
            @else
                <div class="catalog-results-head mb-6">
                    <div>
                        <div class="catalog-panel-title mb-1">Catalog workspace</div>
                        <div class="catalog-panel-subtitle">Showing
                            {{ number_format($showingFrom) }}-{{ number_format($showingTo) }} of
                            {{ number_format($pdfs->total()) }} PDFs.</div>
                    </div>
                    <div class="catalog-chip-row">
                        <span class="catalog-chip">{{ number_format($pdfs->count()) }} on this page</span>
                        <span class="catalog-chip">Sort:
                            {{ $sortOptions[$filters['sort'] ?? 'latest'] ?? 'Newest first' }}</span>
                    </div>
                </div>

                <div class="row g-6 g-xl-8">
                    @foreach ($pdfs as $pdf)
                        <div class="col-xl-6">
                            <div class="catalog-card">
                                <div class="card-body p-8">
                                    <div class="catalog-card-topbar">
                                        <div class="d-flex gap-4">
                                            <span class="symbol symbol-70px">
                                                <span class="symbol-label"
                                                    style="background: linear-gradient(135deg, rgba(255,122,24,0.16), rgba(29,78,216,0.12));">
                                                    <i class="ki-outline ki-file-sheet fs-2x"
                                                        style="color: #123047;"></i>
                                                </span>
                                            </span>
                                            <div>
                                                <div class="d-flex flex-wrap gap-2 mb-3">
                                                    <span class="badge badge-light-info">
                                                        {{ $templateTypeOptions[$pdf->template_type] ?? $pdf->template_type }}
                                                    </span>
                                                    <span
                                                        class="badge badge-light-{{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'success' : 'warning' }} text-capitalize">
                                                        {{ $pdf->visibility }}
                                                    </span>
                                                </div>

                                                <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                                                    class="text-gray-900 text-hover-primary fw-bolder d-inline-block mb-2"
                                                    style="font-size: 1.45rem; line-height: 1.15;">
                                                    {{ $pdf->title }}
                                                </a>

                                                <div class="text-muted fs-7 mb-2">
                                                    {{ $pdf->original_filename ?: 'Uploaded PDF' }}
                                                </div>

                                                @if (auth()->user()?->isAdmin())
                                                    <div class="catalog-admin-pill">
                                                        <i class="ki-outline ki-profile-user fs-5"></i>
                                                        {{ $pdf->user?->email ?? 'Unknown owner' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <a href="{{ route('catalog.pdfs.show', $pdf) }}"
                                            class="btn btn-icon btn-light-primary rounded-circle">
                                            <i class="ki-outline ki-arrow-right fs-2"></i>
                                        </a>
                                    </div>

                                    <div class="text-gray-700 fs-6 mb-2">
                                        {{ \Illuminate\Support\Str::limit($pdf->description ?: 'No description added yet. Open this PDF to configure the workflow focus, preview, and share behavior.', 150) }}
                                    </div>

                                    <div class="catalog-card-metadata">
                                        <div class="catalog-card-meta">
                                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Uploaded</div>
                                            <div class="fw-bold text-gray-900">
                                                {{ $pdf->created_at?->format('d M Y') }}</div>
                                            <div class="text-muted fs-7">{{ $pdf->created_at?->format('h:i A') }}
                                            </div>
                                        </div>
                                        <div class="catalog-card-meta">
                                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">File size</div>
                                            <div class="fw-bold text-gray-900">
                                                {{ number_format(max(($pdf->size ?? 0) / 1048576, 0.01), 2) }} MB</div>
                                            <div class="text-muted fs-7">Stored on
                                                {{ strtoupper($pdf->storage_disk) }}</div>
                                        </div>
                                        <div class="catalog-card-meta">
                                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Access</div>
                                            <div class="fw-bold text-gray-900">
                                                {{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'Shared link ready' : 'Owner only' }}
                                            </div>
                                            <div class="text-muted fs-7">
                                                {{ $pdf->visibility === \App\Models\CatalogPdf::VISIBILITY_PUBLIC ? 'Open in browser' : 'Private workspace' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <a href="{{ route('catalog.pdfs.show', $pdf) }}" class="btn btn-primary">
                                            <i class="ki-outline ki-setting-3 fs-3 me-2"></i>
                                            Manage PDF
                                        </a>
                                        <a href="{{ route('catalog.pdfs.share', $pdf) }}"
                                            class="btn btn-light-success" target="_blank">
                                            <i class="ki-outline ki-eye fs-3 me-2"></i>
                                            Open Share View
                                        </a>
                                        <a href="{{ route('catalog.pdfs.download', $pdf) }}" class="btn btn-light">
                                            <i class="ki-outline ki-cloud-download fs-3 me-2"></i>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-center mt-10">
                    {{ $pdfs->links() }}
                </div>
            @endif
        </div>
    </div>

</x-default-layout>
