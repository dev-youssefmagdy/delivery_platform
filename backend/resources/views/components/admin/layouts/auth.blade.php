@props(['title' => null, 'subtitle' => null])
@php
    $locale = \App\Enums\Common\Locale::tryFromValue(app()->getLocale()) ?? \App\Enums\Common\Locale::default();
    $adminConfig = ['locale' => $locale->value, 'dir' => $locale->direction(), 'csrf' => csrf_token(), 'i18n' => trans('admin.js'), 'maps' => config('admin.maps')];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale->value }}" dir="{{ $locale->direction() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('admin.app_name') }}</title>
    <script type="application/json" id="admin-config">@json($adminConfig)</script>
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-b from-brand-950 to-brand-800 px-4 py-12 font-sans">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <span class="inline-flex size-12 items-center justify-center rounded-xl bg-white/10 text-white">
                <x-admin.icon name="bolt" class="size-6" />
            </span>
            <p class="mt-3 text-lg font-semibold text-white">{{ __('admin.app_name') }}</p>
        </div>

        <div class="card">
            <div class="card-body space-y-6 p-8">
                @if ($title)
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900">{{ $title }}</h1>
                        @if ($subtitle)
                            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>

    <x-admin.toast-stack />
</body>
</html>
