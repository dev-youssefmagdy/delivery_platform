<x-website.layouts.site :meta="$meta">
    <article>
        <header class="bg-brand-50">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
                <a href="{{ $sitePaths->path('services') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline"><span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ __('site.nav.services') }}</a>
                <h1 class="max-w-3xl text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">{{ $cmsPage['title'] }}</h1>
                @if ($cmsPage['summary'])<p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ $cmsPage['summary'] }}</p>@endif
            </div>
        </header>

        @if ($cmsPage['hero_image'])
            <div class="mx-auto mt-8 max-w-6xl px-4"><x-website.image :image="$cmsPage['hero_image']" :eager="true" class="rounded-2xl" /></div>
        @endif
        @if ($cmsPage['body_html'])
            <div class="mx-auto max-w-3xl px-4 py-10"><div class="prose-site">{!! $cmsPage['body_html'] !!}</div></div>
        @endif

        <x-website.blocks :blocks="$cmsPage['blocks']" />

        <section class="mx-auto max-w-6xl px-4 py-8">
            <div class="rounded-2xl bg-brand-800 p-8 text-white">
                <h2 class="text-2xl font-bold">{{ __('site.service.cta_title') }}</h2>
                <p class="mt-2 max-w-2xl text-brand-50">{{ __('site.service.cta_text') }}</p>
                <div class="mt-5"><x-website.store-buttons :links="$appLinks" app="customer" /></div>
                <a class="mt-4 inline-block text-sm font-semibold underline underline-offset-2" href="{{ $sitePaths->path('apps') }}">{{ __('site.apps.all_links') }}</a>
            </div>
        </section>

        @if ($faqs !== [])
            <section class="mx-auto max-w-3xl px-4 py-8">
                <h2 class="mb-5 text-2xl font-bold">{{ __('site.help.faq_title') }}</h2>
                <x-website.faq-list :faqs="$faqs" />
            </section>
        @endif
    </article>
</x-website.layouts.site>
