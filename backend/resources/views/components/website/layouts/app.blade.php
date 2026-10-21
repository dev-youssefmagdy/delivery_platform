@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ?? config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/website.css', 'resources/js/website/app.js'])
    @else
        <style>body{font-family:system-ui,sans-serif;margin:0;background:#f8fafc;color:#0f172a}main{max-width:32rem;margin:0 auto;padding:1rem}</style>
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-lg px-4 py-6">
        {{ $slot }}
    </main>
</body>
</html>
