<!--begin::Navbar-->
<div class="app-navbar flex-shrink-0">
    <div class="app-navbar-item ms-1 ms-md-3">
        @php($sidebarUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0)
        <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-30px h-30px w-md-35px h-md-35px position-relative"
            data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
            {!! getIcon('notification-bing', 'fs-2') !!}
            @if ($sidebarUnreadCount > 0)
                <span
                    class="position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger fs-8">{{ $sidebarUnreadCount > 99 ? '99+' : $sidebarUnreadCount }}</span>
            @endif
        </div>
        @include('partials/menus/_notifications-menu')
    </div>
    <!--begin::User menu-->
    <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
            data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
            @if (Auth::user()->profile_photo_url)
                <img src="{{ \Auth::user()->profile_photo_url }}" class="rounded-3" alt="user" />
            @else
                <div
                    class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', Auth::user()->name) }}">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
            @endif
        </div>
        @include('partials/menus/_user-account-menu')
        <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->
    <!--begin::Header menu toggle-->
    <div class="app-navbar-item d-lg-none ms-2 me-n2" title="Show header menu">
        <div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px" id="kt_app_header_menu_toggle">
            {!! getIcon('element-4', 'fs-1') !!}</div>
    </div>
    <!--end::Header menu toggle-->
    <!--begin::Aside toggle-->
    <!--end::Header menu toggle-->
</div>
<!--end::Navbar-->
