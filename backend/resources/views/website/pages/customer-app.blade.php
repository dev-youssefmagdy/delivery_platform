<x-website.layouts.site :meta="$meta">
    <header class="bg-gradient-to-br from-brand-800 to-brand-600 text-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:py-20">
            <h1 class="max-w-3xl text-3xl font-bold leading-tight sm:text-5xl">{{ $cmsPage['title'] ?? __('site.customer_app.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-brand-50">{{ ($cmsPage['summary'] ?? null) ?: __('site.customer_app.lede') }}</p>
            <div class="mt-8">
                @if ($appLinks !== [])
                    <x-website.store-buttons :links="$appLinks" app="customer" />
                @else
                    <p class="text-sm text-brand-100">{{ __('site.apps.none') }}</p>
                @endif
            </div>
        </div>
    </header>

    @if ($cmsPage && $cmsPage['body_html'])
        <div class="mx-auto max-w-3xl px-4 py-10"><div class="prose-site">{!! $cmsPage['body_html'] !!}</div></div>
    @endif

    @if ($cmsPage && $cmsPage['blocks'] !== [])
        <x-website.blocks :blocks="$cmsPage['blocks']" />
    @else
        <section class="mx-auto max-w-6xl px-4 py-12">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ __('site.customer_app.features_title') }}</h2>
            <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['propose', 'offers', 'track', 'share', 'safety', 'support'] as $feature)
                    <li class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold">{{ __('site.customer_app.features.'.$feature.'.title') }}</h3>
                        <p class="mt-2 leading-7 text-slate-600">{{ __('site.customer_app.features.'.$feature.'.text') }}</p>
                    </li>
                @endforeach
            </ul>
            <p class="mt-8"><a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $sitePaths->path('how-it-works') }}">{{ __('site.nav.how-it-works') }}</a></p>
        </section>
    @endif
</x-website.layouts.site>
