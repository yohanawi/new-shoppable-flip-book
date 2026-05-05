<x-default-layout>

    @section('title')
        Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('dashboard') }}
    @endsection

    @php
        $cards = collect($cards ?? []);
        $catalogRows = collect(data_get($catalogTable ?? [], 'rows', []));
        $recentActivity = collect($recentActivity ?? []);
        $quickLinks = collect($quickLinks ?? []);
        $secondaryRows = collect(data_get($secondaryPanel ?? [], 'rows', []));
        $chartPoints = collect(data_get($chart ?? [], 'points', []))
            ->map(fn($value) => (int) $value)
            ->values();
        $chartLabels = collect(data_get($chart ?? [], 'labels', []))->values();

        if ($chartPoints->isEmpty()) {
            $chartPoints = collect([0, 0, 0, 0, 0, 0, 0]);
            $chartLabels = collect(range(0, 6))->map(
                fn($day) => now()
                    ->subDays(6 - $day)
                    ->format('M d'),
            );
        }

        $chartWidth = 640;
        $chartHeight = 120;
        $leftPadding = 18;
        $rightPadding = 18;
        $topPadding = 18;
        $bottomPadding = 26;
        $usableWidth = $chartWidth - $leftPadding - $rightPadding;
        $usableHeight = $chartHeight - $topPadding - $bottomPadding;
        $maxChartValue = max(1, (int) $chartPoints->max());
        $pointCount = max(1, $chartPoints->count() - 1);

        $chartCoordinates = $chartPoints
            ->values()
            ->map(function ($value, $index) use (
                $leftPadding,
                $topPadding,
                $usableWidth,
                $usableHeight,
                $maxChartValue,
                $pointCount,
            ) {
                $x = $leftPadding + ($usableWidth / $pointCount) * $index;
                $y = $topPadding + ($usableHeight - ($value / $maxChartValue) * $usableHeight);

                return [
                    'x' => round($x, 2),
                    'y' => round($y, 2),
                    'value' => $value,
                ];
            });

        $chartPolyline = $chartCoordinates->map(fn($point) => $point['x'] . ',' . $point['y'])->implode(' ');

        $gridLines = collect(range(0, 4))->map(function ($step) use ($topPadding, $usableHeight, $maxChartValue) {
            $ratio = $step / 4;

            return [
                'y' => round($topPadding + $usableHeight * $ratio, 2),
                'value' => (int) round($maxChartValue * (1 - $ratio)),
            ];
        });
    @endphp

    <style>
        .dashboard-shell {
            background:
                radial-gradient(circle at top right, rgba(80, 205, 137, 0.18), transparent 28%),
                linear-gradient(135deg, #0f172f 0%, #17203d 52%, #1f3f54 100%);
            border-radius: 1.5rem;
            color: #ffffff;
            overflow: hidden;
            position: relative;
        }

        .dashboard-shell::after {
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.08), transparent 40%);
            content: '';
            inset: 0;
            pointer-events: none;
            position: absolute;
        }

        .dashboard-hero-copy {
            max-width: 42rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-eyebrow {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.78rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .dashboard-stat-card {
            background: #ffffff;
            border: 1px solid #eef2f8;
            border-radius: 1.25rem;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        .dashboard-stat-card::before {
            background: linear-gradient(180deg, rgba(80, 205, 137, 0.16), transparent);
            content: '';
            height: 4px;
            inset: 0 0 auto 0;
            position: absolute;
        }

        .dashboard-badge {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.78rem;
            font-weight: 700;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
        }

        .dashboard-badge--up {
            background: rgba(80, 205, 137, 0.16);
            color: #129a53;
        }

        .dashboard-badge--down {
            background: rgba(241, 65, 108, 0.14);
            color: #d9214e;
        }

        .dashboard-badge--flat {
            background: rgba(62, 151, 255, 0.14);
            color: #2675d7;
        }

        .dashboard-panel {
            background: #ffffff;
            border: 1px solid #eef2f8;
            border-radius: 1.35rem;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
            height: 100%;
        }

        .dashboard-chart-svg {
            width: 100%;
            height: auto;
        }

        .dashboard-chart-axis {
            stroke: #dce4f1;
            stroke-dasharray: 5 6;
        }

        .dashboard-chart-line {
            fill: none;
            stroke: #50cd89;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 4;
        }

        .dashboard-chart-point {
            fill: #ffffff;
            stroke: #50cd89;
            stroke-width: 3;
        }

        .dashboard-chart-labels {
            color: #7e8299;
            font-size: 0.76rem;
        }

        .dashboard-table thead th {
            color: #a1a5b7;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding-bottom: 1rem;
            text-transform: uppercase;
        }

        .dashboard-table tbody td {
            border-top: 1px dashed #eef2f8;
            padding-bottom: 1rem;
            padding-top: 1rem;
            vertical-align: middle;
        }

        .dashboard-status {
            border-radius: 0.65rem;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.4rem 0.75rem;
        }

        .dashboard-status--success {
            background: rgba(80, 205, 137, 0.16);
            color: #129a53;
        }

        .dashboard-status--secondary {
            background: rgba(122, 128, 145, 0.12);
            color: #5e6278;
        }

        .dashboard-activity-item+.dashboard-activity-item {
            border-top: 1px dashed #eef2f8;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .dashboard-link-pill {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.65rem 1rem;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .dashboard-link-pill:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .dashboard-metric-chip {
            background: #f8fafd;
            border: 1px solid #eef2f8;
            border-radius: 1rem;
            padding: 1rem;
        }

        @media (max-width: 991.98px) {
            .dashboard-shell {
                border-radius: 1.15rem;
            }

            .dashboard-link-pill {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="d-flex flex-column gap-7">
        <div class="row g-5 g-xl-8 align-items-stretch">
            <div class="col-xxl-4">
                <div class="row g-5">
                    @foreach ($cards as $card)
                        <div class="col-sm-6">
                            <div class="dashboard-stat-card p-6">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-5">
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7 text-uppercase">{{ $card['label'] }}
                                        </div>
                                        <div class="fs-1 fw-bold text-gray-900 mt-2">{{ $card['value'] }}</div>
                                    </div>
                                    @php
                                        $comparisonDirection = data_get($card, 'comparison.direction', 'flat');
                                    @endphp
                                    <span class="dashboard-badge dashboard-badge--{{ $comparisonDirection }}">
                                        {{ data_get($card, 'comparison.label', '0%') }}
                                    </span>
                                </div>
                                <div class="text-gray-500 fw-semibold fs-7">{{ $card['hint'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="dashboard-panel p-6 p-xl-8">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-5 mb-7">
                        <div>
                            <div class="text-gray-900 fw-bold fs-3">{{ data_get($chart ?? [], 'title') }}</div>
                            <div class="text-gray-500 fw-semibold fs-6 mt-1">{{ data_get($chart ?? [], 'subtitle') }}
                            </div>
                        </div>
                        <div class="text-md-end">
                            <div class="fs-1 fw-bold text-gray-900">{{ data_get($chart ?? [], 'value') }}</div>
                            <div class="text-gray-500 fw-semibold fs-7 mt-1">
                                {{ data_get($chart ?? [], 'value_caption') }}</div>
                        </div>
                    </div>

                    <svg class="dashboard-chart-svg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}"
                        role="img" aria-label="Dashboard trend chart">
                        @foreach ($gridLines as $line)
                            <line class="dashboard-chart-axis" x1="{{ $leftPadding }}" y1="{{ $line['y'] }}"
                                x2="{{ $chartWidth - $rightPadding }}" y2="{{ $line['y'] }}"></line>
                            <text x="0" y="{{ $line['y'] + 4 }}" fill="#A1A5B7"
                                font-size="12">{{ number_format($line['value']) }}</text>
                        @endforeach

                        @if ($chartPolyline !== '')
                            <polyline class="dashboard-chart-line" points="{{ $chartPolyline }}"></polyline>
                        @endif

                        @foreach ($chartCoordinates as $point)
                            <circle class="dashboard-chart-point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}"
                                r="5"></circle>
                        @endforeach
                    </svg>

                    <div class="dashboard-chart-labels d-flex justify-content-between mt-4 gap-2">
                        @foreach ($chartLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-panel p-6 p-xl-8">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-6">
                <div>
                    <div class="text-gray-900 fw-bold fs-3">{{ data_get($catalogTable ?? [], 'title') }}</div>
                    <div class="text-gray-500 fw-semibold fs-6 mt-1">{{ data_get($catalogTable ?? [], 'subtitle') }}
                    </div>
                </div>
                <div class="text-gray-500 fw-semibold fs-7">
                    {{ number_format($catalogRows->count()) }} catalog{{ $catalogRows->count() === 1 ? '' : 's' }}
                    shown
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle gs-0 gy-0 mb-0 dashboard-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            @if (($dashboardRole ?? 'customer') === 'admin')
                                <th>Owner</th>
                            @endif
                            <th>Views</th>
                            <th>Avg Time Spent</th>
                            <th>Slices Clicked</th>
                            <th>Completion</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($catalogRows as $row)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-900 fw-bold fs-6">{{ $row['name'] }}</span>
                                        <span class="text-gray-500 fw-semibold fs-7 mt-1">{{ $row['template'] }}</span>
                                    </div>
                                </td>
                                @if (($dashboardRole ?? 'customer') === 'admin')
                                    <td class="text-gray-700 fw-semibold">{{ $row['owner'] }}</td>
                                @endif
                                <td class="text-gray-700 fw-bold">{{ number_format($row['views']) }}</td>
                                <td class="text-gray-700 fw-bold">{{ $row['avg_time'] }}</td>
                                <td class="text-gray-700 fw-bold">{{ number_format($row['slice_clicks']) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-gray-900 fw-bold">{{ $row['completion_rate'] }}%</span>
                                        <div class="progress h-6px w-100 mw-125px bg-light-success">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: {{ min(100, max(0, $row['completion_rate'])) }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="dashboard-status dashboard-status--{{ $row['status_tone'] }}">{{ $row['status'] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ $row['action_url'] }}"
                                        class="btn btn-sm btn-light-primary">{{ $row['action_label'] }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($dashboardRole ?? 'customer') === 'admin' ? 8 : 7 }}"
                                    class="text-center py-10 text-gray-500 fw-semibold">
                                    No catalog activity has been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-5 g-xl-8 align-items-stretch">
            <div class="col-xl-6">
                <div class="dashboard-panel p-6 p-xl-8">
                    <div class="text-gray-900 fw-bold fs-3">{{ data_get($secondaryPanel ?? [], 'title') }}</div>
                    <div class="text-gray-500 fw-semibold fs-6 mt-1 mb-6">
                        {{ data_get($secondaryPanel ?? [], 'subtitle') }}</div>

                    @if (($dashboardRole ?? 'customer') === 'admin')
                        <div class="d-flex flex-column gap-4">
                            @forelse ($secondaryRows as $row)
                                <div
                                    class="dashboard-metric-chip d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                                    <div>
                                        <div class="text-gray-900 fw-bold fs-5">{{ $row['name'] }}</div>
                                        <div class="text-gray-500 fw-semibold fs-7 mt-1">{{ $row['email'] }}</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-4 text-md-end">
                                        <div>
                                            <div class="text-gray-500 fs-8 text-uppercase fw-bold">Catalogs</div>
                                            <div class="text-gray-900 fw-bold">{{ $row['catalogs'] }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 fs-8 text-uppercase fw-bold">Views</div>
                                            <div class="text-gray-900 fw-bold">{{ number_format($row['views']) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 fs-8 text-uppercase fw-bold">Time Spent</div>
                                            <div class="text-gray-900 fw-bold">{{ $row['time_spent'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-gray-500 fw-semibold py-8">No customer workspaces have generated
                                    activity yet.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="dashboard-metric-chip mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-4">
                                <div>
                                    <div class="text-gray-900 fw-bold fs-2">
                                        {{ data_get($secondaryPanel ?? [], 'plan_name') }}</div>
                                    <div class="text-gray-500 fw-semibold fs-6 mt-2">
                                        {{ data_get($secondaryPanel ?? [], 'plan_description') }}</div>
                                </div>
                                <span
                                    class="dashboard-status dashboard-status--{{ data_get($secondaryPanel ?? [], 'analytics_enabled') ? 'success' : 'secondary' }}">
                                    {{ data_get($secondaryPanel ?? [], 'analytics_enabled') ? 'Analytics Enabled' : 'Analytics Locked' }}
                                </span>
                            </div>
                        </div>
                        <div class="row g-4 mb-5">
                            <div class="col-sm-6">
                                <div class="dashboard-metric-chip h-100">
                                    <div class="text-gray-500 fs-8 text-uppercase fw-bold">Catalog Usage</div>
                                    <div class="text-gray-900 fw-bold fs-2 mt-2">
                                        {{ data_get($secondaryPanel ?? [], 'catalogs') }}</div>
                                    <div class="text-gray-500 fw-semibold fs-7 mt-2">Catalogs on your current plan
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="dashboard-metric-chip h-100">
                                    <div class="text-gray-500 fs-8 text-uppercase fw-bold">Storage</div>
                                    <div class="text-gray-900 fw-bold fs-5 mt-2">
                                        {{ data_get($secondaryPanel ?? [], 'storage') }}</div>
                                    <div class="text-gray-500 fw-semibold fs-7 mt-2">Used space across all uploaded
                                        catalogs</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ data_get($secondaryPanel ?? [], 'analytics_url') }}"
                                class="btn btn-primary">{{ data_get($secondaryPanel ?? [], 'analytics_cta') }}</a>
                            <a href="{{ data_get($secondaryPanel ?? [], 'billing_url') }}"
                                class="btn btn-light-primary">Open Billing</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-6">
                <div class="dashboard-panel p-6 p-xl-8">
                    <div class="text-gray-900 fw-bold fs-3">Recent Activity</div>
                    <div class="text-gray-500 fw-semibold fs-6 mt-1 mb-6">Latest events captured from your dashboard
                        scope</div>

                    @forelse ($recentActivity as $activity)
                        <div class="dashboard-activity-item">
                            <div class="d-flex justify-content-between align-items-start gap-4">
                                <div>
                                    <div class="text-gray-900 fw-bold fs-6">{{ $activity['title'] }}</div>
                                    <div class="text-gray-600 fw-semibold fs-7 mt-2">{{ $activity['description'] }}
                                    </div>
                                </div>
                                <span
                                    class="text-gray-500 fw-semibold fs-8 text-nowrap">{{ $activity['timestamp'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-500 fw-semibold py-8">Reader activity will appear here once your catalogs
                            start receiving traffic.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-default-layout>
