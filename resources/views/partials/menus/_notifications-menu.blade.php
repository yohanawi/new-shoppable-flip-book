@php
    $notificationUser = auth()->user();
    $unreadCount = $notificationUser?->unreadNotifications()->count() ?? 0;
    $recentNotifications = $notificationUser?->notifications()->latest()->limit(6)->get() ?? collect();
@endphp

<div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true" id="kt_menu_notifications">
    <div class="d-flex flex-column rounded-top" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
        <div class="px-9 pt-10 pb-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-white fw-semibold mb-0">Notifications</h3>
                <span class="badge badge-light-primary">{{ $unreadCount }} unread</span>
            </div>
            <div class="text-white opacity-75 fs-7">Billing updates, viewer milestones, and admin notices for your
                account.</div>
        </div>
    </div>

    <div class="px-8 py-5 border-bottom d-flex justify-content-between align-items-center">
        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">Open Inbox</a>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-light">Mark all read</button>
            </form>
        @endif
    </div>

    <div class="scroll-y mh-325px my-5 px-8">
        @forelse ($recentNotifications as $notification)
            <div class="d-flex flex-stack py-4 border-bottom border-gray-100">
                <div class="d-flex align-items-start me-3">
                    <div class="symbol symbol-35px me-4">
                        <span
                            class="symbol-label {{ $notification->read_at ? 'bg-light' : 'bg-light-primary' }}">{!! getIcon('notification-bing', 'fs-2 ' . ($notification->read_at ? 'text-gray-500' : 'text-primary')) !!}</span>
                    </div>
                    <div>
                        <div class="fs-6 text-gray-800 fw-bold mb-1">
                            {{ data_get($notification->data, 'title', class_basename($notification->type)) }}</div>
                        <div class="text-gray-500 fs-7 mb-2">
                            {{ \Illuminate\Support\Str::limit((string) data_get($notification->data, 'message', 'Notification received.'), 110) }}
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span
                                class="badge badge-light fs-8">{{ $notification->created_at?->diffForHumans() }}</span>
                            @if (!$notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-color-gray-600 btn-active-color-primary btn-sm p-0">Mark
                                        read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                @if (data_get($notification->data, 'action_url'))
                    <a href="{{ data_get($notification->data, 'action_url') }}"
                        class="btn btn-sm btn-light-primary">{{ data_get($notification->data, 'action_text', 'Open') }}</a>
                @endif
            </div>
        @empty
            <div class="py-15 text-center">
                <div class="text-gray-500 fs-6 mb-3">No notifications yet.</div>
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">Open Inbox</a>
            </div>
        @endforelse
    </div>
</div>
