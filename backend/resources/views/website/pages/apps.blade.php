<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.apps.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.apps.lede') }}</p>
        </div>
    </header>

    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($links === [])
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-slate-700">{{ __('site.apps.none') }}</div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($links as $link)
                    <article class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                        <h2 class="text-lg font-semibold">{{ __('site.apps.app.'.$link['app']) }}</h2>
                        <p class="text-sm text-slate-500">{{ __('site.apps.platform.'.$link['platform']) }}</p>
                        <div class="my-4 size-[168px] rounded-lg bg-white p-1 [&>svg]:size-full" role="img" aria-label="{{ __('site.apps.qr_label', ['app' => __('site.apps.app.'.$link['app']), 'platform' => __('site.apps.platform.'.$link['platform'])]) }}">{!! $link['qr'] !!}</div>
                        <p class="mb-4 text-xs text-slate-500">{{ __('site.apps.qr_hint') }}</p>
                        <a href="{{ $link['url'] }}" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-700 px-5 py-3 font-semibold text-white hover:bg-brand-800">{{ __('site.apps.open_store') }}</a>
                    </article>
                @endforeach
            </div>
        @endif
        <p class="mt-8 text-sm text-slate-600">{{ __('site.apps.driver_note') }} <a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $sitePaths->path('become-provider') }}">{{ __('site.nav.become-provider') }}</a></p>
    </section>
</x-website.layouts.site>
