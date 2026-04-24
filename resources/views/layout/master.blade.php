<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! printHtmlAttributes('html') !!}>
<!--begin::Head-->

<head>
    <base href="" />
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="" />
    <link rel="canonical" href="{{ url()->current() }}" />

    {!! includeFavicon() !!}

    <!--begin::Fonts-->
    {!! includeFonts() !!}
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    @foreach (getGlobalAssets('css') as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Vendor Stylesheets(used by this page)-->
    @foreach (getVendors('css') as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Vendor Stylesheets-->

    <!--begin::Custom Stylesheets(optional)-->
    @foreach (getCustomCss() as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Custom Stylesheets-->

    @livewireStyles
</head>
<!--end::Head-->

<!--begin::Body-->

<body {!! printHtmlClasses('body') !!} {!! printHtmlAttributes('body') !!}>

    @include('partials/theme-mode/_init')

    @yield('content')

    <!--begin::Javascript-->
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    @foreach (getGlobalAssets() as $path)
        {!! sprintf('<script src="%s"></script>', asset($path)) !!}
    @endforeach
    <!--end::Global Javascript Bundle-->

    <!--begin::Vendors Javascript(used by this page)-->
    @foreach (getVendors('js') as $path)
        {!! sprintf('<script src="%s"></script>', asset($path)) !!}
    @endforeach
    <!--end::Vendors Javascript-->

    <!--begin::Custom Javascript(optional)-->
    @foreach (getCustomJs() as $path)
        {!! sprintf('<script src="%s"></script>', asset($path)) !!}
    @endforeach
    <!--end::Custom Javascript-->
    @auth
        <script src="{{ asset('assets/vendor/pusher/pusher.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/laravel-echo/echo.iife.js') }}"></script>
        <script src="{{ asset('assets/js/notifications.js') }}"></script>
    @endauth
    @stack('scripts')
    <!--end::Javascript-->

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('success', (message) => {
                toastr.success(message);
            });
            Livewire.on('error', (message) => {
                toastr.error(message);
            });

            Livewire.on('swal', (message, icon, confirmButtonText) => {
                if (typeof icon === 'undefined') {
                    icon = 'success';
                }
                if (typeof confirmButtonText === 'undefined') {
                    confirmButtonText = 'Ok, got it!';
                }
                Swal.fire({
                    text: message,
                    icon: icon,
                    buttonsStyling: false,
                    confirmButtonText: confirmButtonText,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const flashMessages = [{
                    key: 'success',
                    icon: 'success',
                    title: 'Success'
                },
                {
                    key: 'error',
                    icon: 'error',
                    title: 'Something went wrong'
                },
                {
                    key: 'warning',
                    icon: 'warning',
                    title: 'Please review this'
                },
                {
                    key: 'info',
                    icon: 'info',
                    title: 'Information'
                }
            ];

            const sessionAlerts = {
                success: @json(session()->get('success')),
                error: @json(session()->get('error')),
                warning: @json(session()->get('warning')),
                info: @json(session()->get('info')),
            };

            flashMessages.forEach((flash) => {
                const message = sessionAlerts[flash.key];

                if (!message) {
                    return;
                }

                Swal.fire({
                    title: flash.title,
                    text: message,
                    icon: flash.icon,
                    buttonsStyling: false,
                    confirmButtonText: 'Ok, got it!',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });

            document.querySelectorAll('form[data-swal-confirm]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.swalConfirmed === 'true') {
                        return;
                    }

                    event.preventDefault();

                    Swal.fire({
                        title: form.dataset.swalTitle || 'Are you sure?',
                        text: form.dataset.swalText || 'This action cannot be undone.',
                        icon: form.dataset.swalIcon || 'warning',
                        showCancelButton: true,
                        confirmButtonText: form.dataset.swalConfirmText || 'Yes, continue',
                        cancelButtonText: form.dataset.swalCancelText || 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-light'
                        }
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        form.dataset.swalConfirmed = 'true';
                        form.submit();
                    });
                });
            });
        });
    </script>

    @livewireScripts
</body>
<!--end::Body-->

</html>
