<x-website.layouts.site :meta="$meta">
    <section class="mx-auto max-w-2xl px-4 py-20 text-center">
        <p class="text-6xl font-bold text-brand-700" aria-hidden="true">404</p>
        <h1 class="mt-4 text-3xl font-bold text-slate-900">{{ __('site.not_found.title') }}</h1>
        <p class="mt-3 text-lg text-slate-600">{{ __('site.not_found.text') }}</p>
        <a href="{{ $sitePaths->path('home') }}" class="mt-8 inline-flex rounded-lg bg-brand-700 px-6 py-3 font-semibold text-white hover:bg-brand-800">{{ __('site.not_found.home') }}</a>
    </section>
</x-website.layouts.site>
