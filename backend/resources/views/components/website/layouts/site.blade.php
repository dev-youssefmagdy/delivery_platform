@props(['meta' => null, 'minimal' => false, 'bodyClass' => ''])
{{--
    Public website layout (P5.5): its own head (localized metadata, canonical, hreflang, Open Graph, JSON-LD), header, footer and Vite
    entry; shares nothing with the admin panel. Logical utilities only, so the page mirrors correctly in RTL.
    $meta: App\Support\Website\PageMeta (null = private page, noindex). $chrome comes from the SiteChromeComposer.
--}}
@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $brand = (string) config('website.brand.name');
    $meta ??= app(\App\Support\Website\MetaBuilder::class)->private($brand);
    $ogLocales = ['ar' => 'ar_AR', 'en' => 'en_US'];
    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $other = $locale === 'ar' ? 'en' : 'ar';
    $switchUrl = ($meta->alternates[$other] ?? null);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ config('website.brand.theme_color') }}">
    <title>{{ $meta->title }}</title>
    @if ($meta->description)
        <meta name="description" content="{{ $meta->description }}">
    @endif
    <meta name="robots" content="{{ $meta->robots() }}">
    @if ($meta->noindex)
        <meta name="referrer" content="no-referrer">
    @endif
    @if ($meta->canonical && ! $meta->noindex)
        <link rel="canonical" href="{{ $meta->canonical }}">
    @endif
    @unless ($meta->noindex)
        @foreach ($meta->alternates as $altLocale => $altUrl)
            <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}">
        @endforeach
        @if (isset($meta->alternates['ar']))
            <link rel="alternate" hreflang="x-default" href="{{ $meta->alternates['ar'] }}">
        @endif
        <meta property="og:site_name" content="{{ $brand }}">
        <meta property="og:type" content="{{ $meta->ogType }}">
        <meta property="og:title" content="{{ $meta->title }}">
        @if ($meta->description)
            <meta property="og:description" content="{{ $meta->description }}">
        @endif
        @if ($meta->canonical)
            <meta property="og:url" content="{{ $meta->canonical }}">
        @endif
        <meta property="og:locale" content="{{ $ogLocales[$locale] ?? 'ar_AR' }}">
        @foreach ($meta->alternates as $altLocale => $altUrl)
            @if ($altLocale !== $locale)
                <meta property="og:locale:alternate" content="{{ $ogLocales[$altLocale] ?? $altLocale }}">
            @endif
        @endforeach
        @if ($meta->image)
            <meta property="og:image" content="{{ $meta->image['url'] }}">
            @if ($meta->image['alt'])
                <meta property="og:image:alt" content="{{ $meta->image['alt'] }}">
            @endif
            @if ($meta->image['width'] && $meta->image['height'])
                <meta property="og:image:width" content="{{ $meta->image['width'] }}">
                <meta property="og:image:height" content="{{ $meta->image['height'] }}">
            @endif
        @endif
        <meta name="twitter:card" content="{{ $meta->image ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $meta->title }}">
        @if ($meta->description)
            <meta name="twitter:description" content="{{ $meta->description }}">
        @endif
        @foreach ($meta->jsonLd as $document)
            <script type="application/ld+json">{!! json_encode($document, $jsonFlags) !!}</script>
        @endforeach
    @endunless
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/website.css', 'resources/js/website/app.js'])
    @else
        <style>body{font-family:system-ui,sans-serif;margin:0;background:#f8fafc;color:#0f172a}main{max-width:48rem;margin:0 auto;padding:1rem}</style>
    @endif
</head>
<body class="flex min-h-screen flex-col bg-white text-slate-900 antialiased {{ $bodyClass }}">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-brand-800 focus:shadow">{{ __('site.skip_link') }}</a>

    @if (! $minimal && ! empty($chrome['banner']))
        @php($banner = $chrome['banner'])
        <div class="bg-brand-800 px-4 py-2 text-center text-sm text-white" role="region" aria-label="{{ __('site.announcement') }}">
            <span class="font-semibold">{{ $banner['title'] }}</span>
            @if ($banner['body']) <span class="ms-2 whitespace-pre-line text-brand-100">{{ $banner['body'] }}</span> @endif
            @if ($banner['cta'])
                @php($cta = $sitePaths->resolve($banner['cta']['url']))
                <a class="ms-3 font-semibold underline underline-offset-2" href="{{ $cta['href'] }}" @if ($cta['external']) rel="noopener noreferrer" target="_blank" @endif>{{ $banner['cta']['label'] }}</a>
            @endif
        </div>
    @endif

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur" data-site-header>
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
            <a href="{{ $sitePaths->path('home') }}" class="flex items-center gap-2 font-bold text-brand-800" aria-label="{{ $brand }}">
                <x-website.logo class="size-8" />
                <span class="text-lg">{{ $brand }}</span>
            </a>

            @unless ($minimal)
                <nav class="ms-6 hidden flex-1 items-center gap-1 lg:flex" aria-label="{{ __('site.nav.main') }}">
                    @foreach ($chrome['header'] as $item)
                        <a class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 hover:text-brand-800" href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            @else
                <span class="flex-1"></span>
            @endunless

            @if ($switchUrl)
                <a href="{{ $switchUrl }}" hreflang="{{ $other }}" lang="{{ $other }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('site.switch_language') }}</a>
            @endif

            @unless ($minimal)
                <a href="{{ $sitePaths->path('apps') }}" class="hidden rounded-md bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 sm:inline-block">{{ __('site.nav.get_app') }}</a>
                <button type="button" class="rounded-md p-2 text-slate-700 hover:bg-slate-100 lg:hidden" data-nav-toggle aria-expanded="false" aria-controls="site-mobile-nav" aria-label="{{ __('site.nav.menu') }}">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            @endunless
        </div>
        @unless ($minimal)
            <nav id="site-mobile-nav" class="hidden border-t border-slate-200 bg-white px-4 py-3 lg:hidden" aria-label="{{ __('site.nav.main') }}" data-nav-panel>
                <ul class="space-y-1">
                    @foreach ($chrome['header'] as $item)
                        <li><a class="block rounded-md px-3 py-2 text-base font-medium text-slate-800 hover:bg-slate-100" href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a class="block rounded-md bg-brand-700 px-3 py-2 text-center text-base font-semibold text-white" href="{{ $sitePaths->path('apps') }}">{{ __('site.nav.get_app') }}</a></li>
                </ul>
            </nav>
        @endunless
    </header>

    <main id="main" class="flex-1" tabindex="-1">
        {{ $slot }}
    </main>

    @unless ($minimal)
        <footer class="mt-16 bg-slate-900 text-slate-300">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-4">
                <div class="md:col-span-1">
                    <div class="flex items-center gap-2 font-bold text-white">
                        <x-website.logo class="size-8" />
                        <span class="text-lg">{{ $brand }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-400">{{ __('site.footer.tagline') }}</p>
                    @if (array_filter($chrome['social']))
                        <ul class="mt-4 flex flex-wrap gap-3 text-sm" aria-label="{{ __('site.footer.social') }}">
                            @foreach ($chrome['social'] as $network => $url)
                                @if ($url)
                                    <li><a class="underline-offset-2 hover:text-white hover:underline" href="{{ $url }}" rel="noopener noreferrer me" target="_blank">{{ __('site.social.'.$network) }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
                <nav aria-label="{{ __('site.footer.explore') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('site.footer.explore') }}</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($chrome['footer_explore'] as $item)
                            <li><a class="hover:text-white hover:underline" href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <nav aria-label="{{ __('site.footer.legal') }}">
                    <h2 class="text-sm font-semibold text-white">{{ __('site.footer.legal') }}</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($chrome['footer_legal'] as $item)
                            <li><a class="hover:text-white hover:underline" href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('site.footer.support') }}</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a class="hover:text-white hover:underline" href="{{ $sitePaths->path('contact') }}">{{ __('site.footer.contact_form') }}</a></li>
                        @if ($chrome['contact']['email'])
                            <li><a dir="ltr" class="hover:text-white hover:underline" href="mailto:{{ $chrome['contact']['email'] }}">{{ $chrome['contact']['email'] }}</a></li>
                        @endif
                        @if ($chrome['contact']['phone'])
                            <li><a dir="ltr" class="hover:text-white hover:underline" href="tel:{{ preg_replace('/[^+0-9]/', '', $chrome['contact']['phone']) }}">{{ $chrome['contact']['phone'] }}</a></li>
                        @endif
                    </ul>
                    @if ($chrome['app_links'] !== [])
                        <x-website.store-buttons :links="$chrome['app_links']" app="customer" class="mt-4" small />
                    @endif
                </div>
            </div>
            <div class="border-t border-slate-800 py-5 text-center text-xs text-slate-500">{{ __('site.footer.copyright', ['year' => now()->year, 'brand' => $brand]) }}</div>
        </footer>
    @endunless
</body>
</html>
