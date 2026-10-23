<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.cities.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.cities.lede') }}</p>
        </div>
    </header>

    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($cities === [])
            <p class="rounded-xl bg-slate-50 p-6 text-slate-700">{{ __('site.cities.empty') }}</p>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($cities as $city)
                    <li>
                        @if ($city['page_slug'])
                            <a class="block rounded-xl border border-slate-200 bg-white p-5 text-lg font-semibold text-brand-800 shadow-sm hover:border-brand-300 hover:bg-brand-50" href="{{ $sitePaths->path('city', ['slug' => $city['page_slug']]) }}">{{ $city['name'] }}</a>
                        @else
                            <span class="block rounded-xl border border-slate-200 bg-white p-5 text-lg font-semibold text-slate-800 shadow-sm">{{ $city['name'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="mt-8 text-sm text-slate-600">{{ __('site.cities.note') }}</p>
    </section>
</x-website.layouts.site>
