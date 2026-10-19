@props(['title' => null])
@php
    $locale = \App\Enums\Common\Locale::tryFromValue(app()->getLocale()) ?? \App\Enums\Common\Locale::default();
    $adminConfig = [
        'locale' => $locale->value,
        'dir' => $locale->direction(),
        'csrf' => csrf_token(),
        'i18n' => trans('admin.js'),
        'maps' => config('admin.maps'),
    ];
    $localGuest = app()->environment('local') && config('admin.local_guest_access') === true && auth()->guest();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->direction() }}" class="h-full" data-reload-label="{{ __('admin.common.reload') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('admin.app_name') }}</title>
    <script type="application/json" id="admin-config">@json($adminConfig)</script>
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])
</head>
<body class="h-full font-sans" x-data="adminSidebar()">
    <a href="#admin-main" class="sr-only z-[70] rounded bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:start-4 focus:top-4">{{ __('admin.layout.skip_to_content') }}</a>

    <div class="flex min-h-full">
        <x-admin.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-admin.topbar />

            <main id="admin-main" tabindex="-1" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @if ($localGuest)
                    <x-admin.alert type="warning" class="mb-6">{{ __('admin.layout.local_guest_notice') }}</x-admin.alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-admin.toast-stack />
    <x-admin.modal id="admin-global-modal" size="lg" />
    <x-admin.drawer id="admin-global-drawer" />
    <x-admin.partials.confirm-dialog />
</body>
</html>
