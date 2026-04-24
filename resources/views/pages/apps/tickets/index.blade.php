<x-default-layout>

    @section('title')
        Support Tickets
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.index') }}
    @endsection

    <div id="kt_app_content_container">
        <div class="card card-flush">
            <div class="card-header pt-7">
                <div class="card-title d-flex flex-column">
                    <span class="fw-bold fs-3">{{ $isAdminView ? 'Support Ticket Inbox' : 'My Support Tickets' }}</span>
                    <span class="text-muted fs-7">
                        {{ $isAdminView ? 'Search, filter, and respond to customer tickets.' : 'Track every conversation with the support team.' }}
                    </span>
                </div>
                <div class="card-toolbar gap-2">
                    @if ($isAdminView)
                        <a href="{{ route('tickets.categories.index') }}" class="btn btn-sm btn-light-primary">
                            Manage Categories
                        </a>
                    @else
                        <a href="{{ route('tickets.create') }}" class="btn btn-sm fw-bold btn-primary">
                            <i class="ki-duotone ki-plus fs-3"></i>New Ticket
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0">
                <form method="GET" action="{{ route('tickets.index') }}" class="row g-4 align-items-end mb-8">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="text" name="search" class="form-control form-control-solid"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Ticket ID, subject{{ $isAdminView ? ', customer name or email' : '' }}">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All statuses</option>
                            <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>Open
                            </option>
                            <option value="in_progress"
                                {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>
                                In Progress
                            </option>
                            <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>
                                Closed</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All priorities</option>
                            <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High
                            </option>
                            <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>
                                Medium
                            </option>
                            <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ (string) ($filters['category'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-3">
                        <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                        <a href="{{ route('tickets.index') }}" class="btn btn-light flex-fill">Reset</a>
                    </div>
                </form>

                @if ($tickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>ID</th>
                                    @if ($isAdminView)
                                        <th>Customer</th>
                                    @endif
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Messages</th>
                                    <th>Feedback</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tickets as $ticket)
                                    <tr>
                                        <td>#{{ $ticket->id }}</td>
                                        @if ($isAdminView)
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="fw-bold text-gray-900">{{ $ticket->user?->name ?? 'Unknown customer' }}</span>
                                                    <span class="text-muted fs-7">{{ $ticket->user?->email }}</span>
                                                </div>
                                            </td>
                                        @endif
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-900">{{ $ticket->subject }}</span>
                                                @if ($ticket->attachment_name)
                                                    <span class="text-muted fs-7">Attachment:
                                                        {{ $ticket->attachment_name }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td><span class="badge badge-light-info">{{ $ticket->category_name }}</span>
                                        </td>
                                        <td>
                                            @if ($ticket->priority === 'high')
                                                <span class="badge badge-light-danger">High</span>
                                            @elseif($ticket->priority === 'medium')
                                                <span class="badge badge-light-warning">Medium</span>
                                            @else
                                                <span class="badge badge-light-success">Low</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($ticket->status === 'open')
                                                <span class="badge badge-light-primary">Open</span>
                                            @elseif($ticket->status === 'in_progress')
                                                <span class="badge badge-light-warning">In Progress</span>
                                            @else
                                                <span class="badge badge-light-success">Closed</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->messages_count }}</td>
                                        <td>
                                            @if ($ticket->feedback_rating)
                                                <span
                                                    class="text-warning fw-bold">{{ str_repeat('★', (int) $ticket->feedback_rating) }}</span>
                                                <span class="text-muted">{{ $ticket->feedback_rating }}/5</span>
                                            @else
                                                <span class="text-muted">No rating</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('tickets.show', $ticket) }}"
                                                class="btn btn-sm btn-light btn-active-light-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($tickets->hasPages())
                        <div class="pt-8">
                            {{ $tickets->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-20">
                        <i class="ki-duotone ki-message-question fs-5x text-muted mb-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <h3 class="text-muted">No Support Tickets Found</h3>
                        @if ($isAdminView)
                            <p class="text-muted fs-5 mb-0">Adjust the filters or wait for new customer requests.</p>
                        @else
                            <p class="text-muted fs-5 mb-5">Create your first support ticket to start a conversation.
                            </p>
                            <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                                <i class="ki-duotone ki-plus fs-3"></i>Create Ticket
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>
