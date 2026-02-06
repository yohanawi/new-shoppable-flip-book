<x-default-layout>

    @section('title')
        Support Tickets
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('tickets.index') }}
    @endsection

    <div id="kt_app_content_container">

        <div class="card">
            <div class="d-flex align-items-center gap-2 justify-content-end p-5">
                <a href="{{ route('tickets.create') }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="ki-duotone ki-plus fs-3"></i>New Ticket
                </a>
            </div>
            <div class="card-body">
                @if (count($tickets) > 0)
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tickets as $ticket)
                                    <tr>
                                        <td>#{{ $ticket->id }}</td>
                                        <td>{{ $ticket->subject }}</td>
                                        <td><span class="badge badge-light-info">{{ ucfirst($ticket->category) }}</span>
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
                                        <td>{{ $ticket->created_at->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('tickets.show', $ticket) }}"
                                                class="btn btn-sm btn-light btn-active-light-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-20">
                        <i class="ki-duotone ki-message-question fs-5x text-muted mb-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <h3 class="text-muted">No Support Tickets</h3>
                        <p class="text-muted fs-5 mb-5">Need help? Create a support ticket</p>
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                            <i class="ki-duotone ki-plus fs-3"></i>Create Ticket
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-default-layout>
