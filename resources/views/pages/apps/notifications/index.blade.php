<x-default-layout>

    @section('title')
        Notifications
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('notifications.index') }}
    @endsection

    @if (session('success'))
        <div class="alert alert-success mb-8">{{ session('success') }}</div>
    @endif

    <div class="card mb-8">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
            <div>
                <h3 class="mb-1">Your Notifications</h3>
                <div class="text-muted">
                    Track billing reminders, viewer milestones, and direct admin notices in one inbox.
                </div>
            </div>

            <div class="d-flex gap-3 align-items-center">
                <span class="badge badge-light-primary fs-6 px-4 py-3">
                    Unread: {{ $unreadCount }}
                </span>

                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-primary">
                            Mark All Read
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            @forelse ($notifications as $notification)
                <div
                    class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 py-6 {{ !$loop->last ? 'border-bottom border-gray-200' : '' }}">

                    <div class="d-flex align-items-start gap-4">
                        <div class="symbol symbol-45px">
                            <span class="symbol-label {{ $notification->read_at ? 'bg-light' : 'bg-light-primary' }}">
                                {!! getIcon('notification-bing', 'fs-2 ' . ($notification->read_at ? 'text-gray-500' : 'text-primary')) !!}
                            </span>
                        </div>

                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <h4 class="mb-0">
                                    {{ data_get($notification->data, 'title', class_basename($notification->type)) }}
                                </h4>

                                @if (!$notification->read_at)
                                    <span class="badge badge-light-danger">Unread</span>
                                @endif
                            </div>

                            <div class="text-gray-700 mb-2">
                                {{ data_get($notification->data, 'message', 'Notification received.') }}
                            </div>

                            <div class="text-muted fs-7">
                                {{ $notification->created_at?->format('d M Y H:i') }}
                                •
                                {{ $notification->created_at?->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        @if (data_get($notification->data, 'action_url'))
                            <a href="{{ data_get($notification->data, 'action_url') }}"
                                class="btn btn-sm btn-light-primary">
                                {{ data_get($notification->data, 'action_text', 'Open') }}
                            </a>
                        @endif

                        @if (!$notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-15 text-center">
                    <div class="text-gray-500 fs-5">
                        You have no notifications yet.
                    </div>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if ($notifications->hasPages())
                <div class="card-footer border-0 pt-8">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-5">

                        {{-- Pagination Info --}}
                        <div class="text-muted fs-6">
                            Showing
                            <span class="fw-bold text-dark">
                                {{ $notifications->firstItem() }}
                            </span>
                            to
                            <span class="fw-bold text-dark">
                                {{ $notifications->lastItem() }}
                            </span>
                            of
                            <span class="fw-bold text-primary">
                                {{ $notifications->total() }}
                            </span>
                            notifications
                        </div>

                        {{-- Pagination Links --}}
                        <div class="d-flex justify-content-center">
                            <div class="pagination pagination-outline pagination-circle">
                                {{ $notifications->links('pagination::bootstrap-5') }}
                            </div>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>

</x-default-layout>
