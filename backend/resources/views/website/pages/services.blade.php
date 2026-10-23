<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.services.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.services.lede') }}</p>
        </div>
    </header>

    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($services === [])
            <p class="rounded-xl bg-slate-50 p-6 text-slate-700">{{ __('site.services.empty') }}</p>
        @else
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ $sitePaths->path('service', ['slug' => $service['slug']]) }}" class="block h-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-brand-300 hover:shadow">
                            @if ($service['hero_image'])
                                <x-website.image :image="$service['hero_image']" sizes="(min-width: 1024px) 384px, 100vw" />
                            @endif
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-slate-900">{{ $service['title'] }}</h2>
                                @if ($service['summary'])<p class="mt-2 leading-7 text-slate-600">{{ $service['summary'] }}</p>@endif
                                <span class="mt-4 inline-block text-sm font-semibold text-brand-700">{{ __('site.read_more') }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @foreach ($banners as $banner)
        <section class="mx-auto my-8 max-w-6xl px-4">
            <div class="rounded-2xl bg-accent-400/20 p-6">
                <h2 class="text-lg font-bold">{{ $banner['title'] }}</h2>
                @if ($banner['body'])<p class="mt-2 whitespace-pre-line text-slate-700">{{ $banner['body'] }}</p>@endif
                @if ($banner['cta'])<div class="mt-4"><x-website.cta-link :url="$banner['cta']['url']">{{ $banner['cta']['label'] }}</x-website.cta-link></div>@endif
            </div>
        </section>
    @endforeach

    <p class="mx-auto max-w-6xl px-4 text-sm text-slate-600">{{ __('site.services.fare_note') }}</p>
</x-website.layouts.site>
