{{-- Generic CMS page (how it works, safety, about, city): title, summary, hero image, rich text and sections. --}}
@php($backKey ??= null)
<x-website.layouts.site :meta="$meta">
    <article>
        <header class="bg-brand-50">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
                @if ($backKey)
                    <a href="{{ $sitePaths->path($backKey) }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline"><span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ __('site.nav.'.$backKey) }}</a>
                @endif
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
    </article>

    @if (! empty($appLinks))
        <section class="mx-auto max-w-6xl px-4 py-10">
            <h2 class="text-xl font-bold">{{ __('site.apps.title') }}</h2>
            <div class="mt-4"><x-website.store-buttons :links="$appLinks" app="customer" /></div>
        </section>
    @endif
</x-website.layouts.site>
