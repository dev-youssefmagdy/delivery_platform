<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.blog.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.blog.lede') }}</p>
        </div>
    </header>

    <section class="mx-auto max-w-6xl px-4 py-10">
        @if ($articles === [])
            <p class="rounded-xl bg-slate-50 p-6 text-slate-700">{{ __('site.blog.empty') }}</p>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <li>
                        <a href="{{ $sitePaths->path('blog-article', ['slug' => $article['slug']]) }}" class="block h-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-brand-300 hover:shadow">
                            @if ($article['cover_image'])<x-website.image :image="$article['cover_image']" sizes="(min-width: 1024px) 384px, 100vw" />@endif
                            <div class="p-6">
                                <h2 class="text-lg font-semibold text-slate-900">{{ $article['title'] }}</h2>
                                @if ($article['published_at'])<p class="mt-1 text-xs text-slate-500"><time datetime="{{ $article['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($article['published_at'])->translatedFormat('j F Y') }}</time></p>@endif
                                @if ($article['summary'])<p class="mt-3 leading-7 text-slate-600">{{ $article['summary'] }}</p>@endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-website.layouts.site>
