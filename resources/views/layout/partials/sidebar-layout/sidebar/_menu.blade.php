<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true"
        data-kt-scroll-activate="true" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
        data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
        <div class="menu menu-column menu-rounded menu-sub-indention px-3 fw-semibold fs-6" id="#kt_app_sidebar_menu"
            data-kt-menu="true" data-kt-menu-expand="false">
            @canany(['admin.dashboard.view', 'customer.dashboard.view'])
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('dashboard') ? 'here show' : '' }}">
                    <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <span class="menu-bullet">
                            <span class="menu-icon">{!! getIcon('element-11', 'fs-2') !!}</span>
                        </span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </div>
            @endcanany
            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Apps</span>
                </div>
            </div>
            @if (auth()->user()?->isAdmin())
                <div data-kt-menu-trigger="click"
                    class="menu-item menu-accordion {{ request()->routeIs('user-management.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">{!! getIcon('people', 'fs-2') !!}</span>
                        <span class="menu-title">User Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('user-management.users.*') ? 'active' : '' }}"
                                href="{{ route('user-management.users.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Users</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}"
                                href="{{ route('user-management.roles.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Roles</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}"
                                href="{{ route('user-management.permissions.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Permissions</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
                        href="{{ route('admin.customers.index') }}">
                        <span class="menu-icon">{!! getIcon('people', 'fs-2') !!}</span>
                        <span class="menu-title">Customers</span>
                    </a>
                </div>
            @endif
            <div data-kt-menu-trigger="click"
                class="menu-item menu-accordion {{ request()->routeIs('catalog.*') ? 'here show' : '' }}">
                <span class="menu-link">
                    <span class="menu-icon">{!! getIcon('abstract-28', 'fs-2') !!}</span>
                    <span class="menu-title">Catalog</span>
                    <span class="menu-arrow"></span>
                </span>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('catalog.pdfs.create') ? 'active' : '' }}"
                            href="{{ route('catalog.pdfs.create') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Upload Catalog</span>
                        </a>
                    </div>
                </div>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('catalog.pdfs.*') ? 'active' : '' }}"
                            href="{{ route('catalog.pdfs.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Catalogs</span>
                        </a>
                    </div>
                </div>
                <div class="menu-sub menu-sub-accordion">
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('catalog.pdfs.share-preview.*') ? 'active' : '' }}"
                            href="{{ route('catalog.pdfs.share-preview.index') }}">
                            <span class="menu-bullet">
                                <span class="bullet bullet-dot"></span>
                            </span>
                            <span class="menu-title">Share Preview Studio</span>
                        </a>
                    </div>
                </div>
            </div>
            @if (auth()->user()?->isCustomer())
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Billing</span>
                    </div>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('billing.*') ? 'active' : '' }}"
                        href="{{ route('billing.index') }}">
                        <span class="menu-icon">{!! getIcon('wallet', 'fs-2') !!}</span>
                        <span class="menu-title">Billing</span>
                    </a>
                </div>
            @endif
            @if (auth()->user()?->isAdmin() || auth()->user()?->isCustomer())
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Analytics</span>
                    </div>
                </div>
                @if (auth()->user()?->isAdmin() || app(\App\Services\BillingManager::class)->hasFeature(auth()->user(), 'analytics'))
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}"
                            href="{{ route('analytics.index') }}">
                            <span class="menu-icon">{!! getIcon('chart-line', 'fs-2') !!}</span>
                            <span class="menu-title">Book Analytics</span>
                        </a>
                    </div>
                @elseif (auth()->user()?->isCustomer())
                    <div class="menu-item">
                        <a class="menu-link" href="{{ route('billing.index') }}">
                            <span class="menu-icon">{!! getIcon('chart-line', 'fs-2') !!}</span>
                            <span class="menu-title">Unlock Analytics</span>
                        </a>
                    </div>
                @endif
            @endif
            @if (auth()->user()?->isAdmin())
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Admin Billing</span>
                    </div>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}"
                        href="{{ route('admin.billing.index') }}">
                        <span class="menu-icon">{!! getIcon('chart-simple', 'fs-2') !!}</span>
                        <span class="menu-title">Billing Dashboard</span>
                    </a>
                </div>
            @endif
            <div class="menu-item pt-5">
                <div class="menu-content">
                    <span class="menu-heading fw-bold text-uppercase fs-7">Support</span>
                </div>
            </div>
            @if (auth()->user()?->isAdmin())
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                        href="{{ route('notifications.index') }}">
                        <span class="menu-icon">{!! getIcon('notification-bing', 'fs-2') !!}</span>
                        <span class="menu-title">Notifications</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
                        href="{{ route('admin.notifications.index') }}">
                        <span class="menu-icon">{!! getIcon('notification-status', 'fs-2') !!}</span>
                        <span class="menu-title">Notification Audit</span>
                    </a>
                </div>
            @endif
            <div class="menu-item">
                <a class="menu-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}"
                    href="{{ route('tickets.index') }}">
                    <span class="menu-icon">{!! getIcon('support-24', 'fs-2') !!}</span>
                    <span class="menu-title">Support Tickets</span>
                </a>
            </div>
        </div>
    </div>
</div>
