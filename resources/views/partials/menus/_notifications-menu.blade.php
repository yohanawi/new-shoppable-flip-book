@php
    $notificationUser = auth()->user();
    $unreadCount = $notificationUser?->unreadNotifications()->count() ?? 0;
    $broadcastConnection = config('broadcasting.default');
    $realtimeEnabled = $broadcastConnection === 'pusher' && filled(env('PUSHER_APP_KEY'));
@endphp

<div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true" id="kt_menu_notifications"
    data-notification-center data-feed-url="{{ route('notifications.feed') }}"
    data-read-all-url="{{ route('notifications.read-all') }}"
    data-read-url-template="{{ route('notifications.read', ['notification' => '__notification__']) }}"
    data-inbox-url="{{ route('notifications.index') }}" data-user-id="{{ $notificationUser?->getAuthIdentifier() }}"
    data-can-manage="{{ $notificationUser?->can('notifications.manage') ? '1' : '0' }}"
    data-initial-unread-count="{{ $unreadCount }}" data-realtime-enabled="{{ $realtimeEnabled ? '1' : '0' }}"
    data-pusher-key="{{ (string) env('PUSHER_APP_KEY', '') }}"
    data-pusher-cluster="{{ (string) env('PUSHER_APP_CLUSTER', '') }}"
    data-pusher-host="{{ (string) env('PUSHER_HOST', '') }}" data-pusher-port="{{ (string) env('PUSHER_PORT', '') }}"
    data-pusher-scheme="{{ (string) env('PUSHER_SCHEME', 'https') }}"
    data-auth-endpoint="{{ url('/broadcasting/auth') }}">
    <div class="d-flex flex-column rounded-top" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);">
        <div class="px-9 pt-10 pb-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-white fw-semibold mb-0">Notifications</h3>
                <span class="badge badge-light-primary" data-notification-unread-label>{{ $unreadCount }}
                    unread</span>
            </div>
            <div class="text-white opacity-75 fs-7" data-notification-connection-status>Loading your notification
                feed...</div>
        </div>
    </div>

    <div class="px-8 py-5 border-bottom d-flex justify-content-between align-items-center gap-3">
        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">Open Inbox</a>
        <button type="button" class="btn btn-sm btn-light {{ $unreadCount > 0 ? '' : 'd-none' }}"
            data-notification-mark-all>
            Mark all read
        </button>
    </div>

    <div class="scroll-y mh-325px my-5 px-8">
        <div class="py-10 text-center" data-notification-loading>
            <div class="text-gray-500 fs-6">Loading notifications...</div>
        </div>
        <div class="d-none" data-notification-list></div>
        <div class="py-15 text-center d-none" data-notification-empty>
            <div class="text-gray-500 fs-6 mb-3">No notifications yet.</div>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light-primary">Open Inbox</a>
        </div>
    </div>
</div>
