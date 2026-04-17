<x-default-layout>

    @section('title')
        Analytics
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('analytics.index') }}
    @endsection

    <div class="row g-6 g-xl-9 mb-8">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-bold fs-7 mb-2">Books</div>
                    <div class="d-flex align-items-end gap-3">
                        <span class="text-gray-900 fw-bold fs-2hx">{{ $summary['books_count'] }}</span>
                        <span class="badge badge-light-primary">Owned</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-bold fs-7 mb-2">Book Views</div>
                    <div class="d-flex align-items-end gap-3">
                        <span class="text-gray-900 fw-bold fs-2hx">{{ number_format($summary['views_count']) }}</span>
                        <span class="badge badge-light-success">Open events</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-bold fs-7 mb-2">Readers</div>
                    <div class="d-flex align-items-end gap-3">
                        <span class="text-gray-900 fw-bold fs-2hx">{{ number_format($summary['readers_count']) }}</span>
                        <span class="badge badge-light-info">Unique</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-gray-500 fw-bold fs-7 mb-2">Time Spent</div>
                    <div class="d-flex align-items-end gap-3">
                        <span class="text-gray-900 fw-bold fs-2">{{ $summary['time_spent_human'] }}</span>
                        <span class="badge badge-light-warning">All books</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
            <div>
                <h3 class="mb-1">Customer Book Analytics</h3>
                <div class="text-muted">Track book views, readers, total reading time, and slicer hotspot clicks for
                    your catalog books.</div>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <div class="badge badge-light-dark fs-6 px-4 py-3">Slice Clicks:
                    {{ number_format($summary['slice_click_count']) }}</div>
                <a href="{{ route('catalog.pdfs.index') }}" class="btn btn-light-primary">Manage Books</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Per-Book Metrics</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            @if ($books->isEmpty())
                <div class="py-15 text-center">
                    <div class="text-gray-500 fs-5 mb-5">You do not have any books yet.</div>
                    <a href="{{ route('catalog.pdfs.create') }}" class="btn btn-primary">Upload Your First Book</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Book</th>
                                <th>Template</th>
                                <th>Book Views</th>
                                <th>Readers</th>
                                <th>Time Spent</th>
                                <th>Slice Clicks</th>
                                <th>Last Activity</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @foreach ($books as $book)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('catalog.pdfs.show', $book['pdf']) }}"
                                                class="text-gray-900 text-hover-primary fw-bold mb-1">{{ $book['pdf']->title }}</a>
                                            <span
                                                class="text-muted fs-7">{{ $book['pdf']->original_filename ?: 'Untitled PDF' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">
                                            {{ \App\Models\CatalogPdf::templateTypeOptions()[$book['pdf']->template_type] ?? $book['pdf']->template_type }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($book['views_count']) }}</td>
                                    <td>{{ number_format($book['readers_count']) }}</td>
                                    <td>{{ $book['time_spent_human'] }}</td>
                                    <td>{{ number_format($book['slice_click_count']) }}</td>
                                    <td>{{ $book['last_viewed_at']?->diffForHumans() ?? 'No activity yet' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ $book['share_url'] }}"
                                                class="btn btn-sm btn-light-info">Open</a>
                                            <a href="{{ $book['manage_url'] }}"
                                                class="btn btn-sm btn-light-primary">Manage</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</x-default-layout>
