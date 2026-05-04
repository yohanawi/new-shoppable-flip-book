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
    <script>
        (function() {
            function mergeCustomClasses(baseClasses, extraClasses) {
                return Object.assign({}, baseClasses, extraClasses || {});
            }

            window.showSweetAlertConfirmation = function(options = {}) {
                const baseOptions = {
                    title: 'Are you sure?',
                    text: 'This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, continue',
                    cancelButtonText: 'Cancel',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                };

                const finalOptions = Object.assign({}, baseOptions, options);
                finalOptions.customClass = mergeCustomClasses(baseOptions.customClass, options.customClass);

                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    return Promise.resolve(window.confirm(finalOptions.text || finalOptions.title || baseOptions.text));
                }

                return window.Swal.fire(finalOptions).then((result) => !!result.isConfirmed);
            };

            window.showDeleteConfirmation = function(options = {}) {
                return window.showSweetAlertConfirmation(Object.assign({
                    title: 'Delete item?',
                    text: 'This action is permanent and cannot be undone.',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    reverseButtons: false,
                    focusCancel: true,
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    }
                }, options));
            };

            window.createUnsavedChangesGuard = function(options = {}) {
                const guardId = 'unsaved-guard-' + Math.random().toString(36).slice(2);
                const defaults = {
                    title: 'Unsaved Changes',
                    text: 'You have unsaved changes. If you leave now, changes will be permanently lost.',
                    interceptLinks: true,
                    interceptHistory: true,
                    onStay: null,
                    onDiscard: null,
                    isDirty: function() {
                        return false;
                    }
                };

                const settings = Object.assign({}, defaults, options);
                const state = {
                    bypassNavigation: false,
                    promptPromise: null,
                    historyTrapReady: false,
                };

                const canUseHistoryTrap = settings.interceptHistory && !!window.history && typeof window.history
                    .pushState === 'function' && typeof window.history.replaceState === 'function';

                function runHook(hook) {
                    if (typeof hook !== 'function') {
                        return;
                    }

                    try {
                        hook();
                    } catch (error) {
                        console.error(error);
                    }
                }

                function isDirty() {
                    if (state.bypassNavigation) {
                        return false;
                    }

                    try {
                        return !!settings.isDirty();
                    } catch (error) {
                        console.error(error);
                        return false;
                    }
                }

                function shouldIgnoreLink(anchor, event) {
                    if (!anchor || anchor.hasAttribute('data-unsaved-guard-ignore')) {
                        return true;
                    }

                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event
                        .shiftKey ||
                        event.altKey) {
                        return true;
                    }

                    const href = anchor.getAttribute('href') || '';
                    if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') ||
                        href
                        .startsWith('tel:')) {
                        return true;
                    }

                    if (anchor.target && anchor.target !== '_self') {
                        return true;
                    }

                    if (anchor.hasAttribute('download')) {
                        return true;
                    }

                    const url = new URL(anchor.href, window.location.href);
                    if (url.origin !== window.location.origin) {
                        return true;
                    }

                    if (url.href === window.location.href && url.hash) {
                        return true;
                    }

                    return false;
                }

                function showPrompt() {
                    if (state.promptPromise) {
                        return state.promptPromise;
                    }

                    if (!window.Swal || typeof window.Swal.fire !== 'function') {
                        state.promptPromise = Promise.resolve(window.confirm(
                            settings.text +
                            '\n\nPress OK to stay and save, or Cancel to discard your changes.'
                        )).then((shouldStay) => {
                            state.promptPromise = null;
                            return shouldStay;
                        });

                        return state.promptPromise;
                    }

                    state.promptPromise = window.Swal.fire({
                        title: settings.title,
                        text: settings.text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Stay & Save',
                        cancelButtonText: 'Discard',
                        buttonsStyling: false,
                        reverseButtons: false,
                        focusConfirm: true,
                        customClass: {
                            popup: 'rounded-4',
                            confirmButton: 'btn btn-success',
                            cancelButton: 'btn btn-danger'
                        }
                    }).then((result) => {
                        state.promptPromise = null;
                        return !(result.dismiss === window.Swal.DismissReason.cancel);
                    });

                    return state.promptPromise;
                }

                function navigateWithDiscard(action) {
                    state.bypassNavigation = true;
                    runHook(settings.onDiscard);
                    action();
                }

                function confirmNavigation(action) {
                    if (!isDirty()) {
                        action();
                        return Promise.resolve(true);
                    }

                    return showPrompt().then((shouldStay) => {
                        if (shouldStay) {
                            runHook(settings.onStay);
                            return false;
                        }

                        navigateWithDiscard(action);
                        return true;
                    });
                }

                function onDocumentClick(event) {
                    if (!settings.interceptLinks) {
                        return;
                    }

                    const anchor = event.target.closest('a[href]');
                    if (shouldIgnoreLink(anchor, event)) {
                        return;
                    }

                    if (!isDirty()) {
                        return;
                    }

                    event.preventDefault();
                    const href = anchor.href;
                    confirmNavigation(function() {
                        window.location.assign(href);
                    });
                }

                function prepareHistoryTrap() {
                    if (!canUseHistoryTrap || state.historyTrapReady) {
                        return;
                    }

                    const baseState = Object.assign({}, window.history.state || {}, {
                        __unsavedGuardBase: guardId
                    });

                    window.history.replaceState(baseState, '', window.location.href);
                    window.history.pushState(Object.assign({}, baseState, {
                        __unsavedGuardTrap: true
                    }), '', window.location.href);

                    state.historyTrapReady = true;
                }

                function onPopState(event) {
                    if (!canUseHistoryTrap || state.bypassNavigation) {
                        return;
                    }

                    const currentState = event.state || {};
                    const isGuardBaseState = currentState.__unsavedGuardBase === guardId && currentState
                        .__unsavedGuardTrap !== true;

                    if (!isGuardBaseState) {
                        return;
                    }

                    if (!isDirty()) {
                        state.bypassNavigation = true;
                        window.history.back();
                        return;
                    }

                    window.history.pushState(Object.assign({}, currentState, {
                        __unsavedGuardTrap: true
                    }), '', window.location.href);

                    showPrompt().then((shouldStay) => {
                        if (shouldStay) {
                            runHook(settings.onStay);
                            return;
                        }

                        navigateWithDiscard(function() {
                            window.history.back();
                        });
                    });
                }

                function onBeforeUnload(event) {
                    if (!isDirty()) {
                        return;
                    }

                    event.preventDefault();
                    event.returnValue = '';
                }

                document.addEventListener('click', onDocumentClick, true);
                window.addEventListener('beforeunload', onBeforeUnload);
                window.addEventListener('popstate', onPopState);
                prepareHistoryTrap();

                return {
                    confirmNavigation: confirmNavigation,
                    allowNextNavigation: function() {
                        state.bypassNavigation = true;
                    },
                    resetNavigationBypass: function() {
                        state.bypassNavigation = false;
                    },
                    destroy: function() {
                        document.removeEventListener('click', onDocumentClick, true);
                        window.removeEventListener('beforeunload', onBeforeUnload);
                        window.removeEventListener('popstate', onPopState);
                    }
                };
            };
        })();
    </script>
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

            function isDeleteForm(form) {
                if (!(form instanceof HTMLFormElement)) {
                    return false;
                }

                const rawMethod = String(form.getAttribute('method') || form.method || 'GET').toUpperCase();
                const spoofedMethod = String(form.querySelector('input[name="_method"]')?.value || '').toUpperCase();

                return rawMethod === 'DELETE' || spoofedMethod === 'DELETE';
            }

            function submitConfirmedForm(form, submitter) {
                form.dataset.swalConfirmed = 'true';

                if (typeof form.requestSubmit === 'function' && submitter instanceof HTMLElement) {
                    form.requestSubmit(submitter);
                    return;
                }

                form.submit();
            }

            function confirmationOptionsForForm(form, isDelete) {
                if (isDelete) {
                    return {
                        title: form.dataset.deleteSwalTitle || form.dataset.swalTitle || 'Delete item?',
                        text: form.dataset.deleteSwalText || form.dataset.swalText ||
                            'This action is permanent and cannot be undone.',
                        confirmButtonText: form.dataset.deleteSwalConfirmText || form.dataset.swalConfirmText ||
                            'Delete',
                        cancelButtonText: form.dataset.deleteSwalCancelText || form.dataset.swalCancelText ||
                            'Cancel'
                    };
                }

                return {
                    title: form.dataset.swalTitle || 'Are you sure?',
                    text: form.dataset.swalText || 'This action cannot be undone.',
                    icon: form.dataset.swalIcon || 'warning',
                    confirmButtonText: form.dataset.swalConfirmText || 'Yes, continue',
                    cancelButtonText: form.dataset.swalCancelText || 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                };
            }

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (form.dataset.swalConfirmed === 'true') {
                    delete form.dataset.swalConfirmed;
                    return;
                }

                const deleteForm = isDeleteForm(form);
                const needsConfirmation = deleteForm || form.hasAttribute('data-swal-confirm');

                if (!needsConfirmation) {
                    return;
                }

                event.preventDefault();
                const submitter = event.submitter;

                const options = confirmationOptionsForForm(form, deleteForm);
                const confirmationPromise = deleteForm ? window.showDeleteConfirmation(options) : window
                    .showSweetAlertConfirmation(options);

                confirmationPromise.then((confirmed) => {
                    if (!confirmed) {
                        return;
                    }

                    submitConfirmedForm(form, submitter);
                });
            }, true);
        });
    </script>

    @livewireScripts
</body>
<!--end::Body-->

</html>
