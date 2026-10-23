<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.help.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.help.lede') }}</p>
        </div>
    </header>

    <div class="mx-auto max-w-4xl px-4 py-10">
        <section aria-labelledby="faq-title">
            <h2 id="faq-title" class="mb-5 text-2xl font-bold">{{ __('site.help.faq_title') }}</h2>
            @if ($faqs['faqs'] === [])
                <p class="rounded-xl bg-slate-50 p-6 text-slate-700">{{ __('site.help.empty') }}</p>
            @else
                @php($grouped = collect($faqs['faqs'])->groupBy(fn ($faq) => $faq['category_slug'] ?? ''))
                @foreach ($faqs['categories'] as $category)
                    @if ($grouped->has($category['slug']))
                        <h3 class="mb-3 mt-8 text-lg font-semibold text-brand-800">{{ $category['name'] }}</h3>
                        <x-website.faq-list :faqs="$grouped[$category['slug']]->all()" />
                    @endif
                @endforeach
                @if ($grouped->has(''))
                    <h3 class="mb-3 mt-8 text-lg font-semibold text-brand-800">{{ __('site.help.other') }}</h3>
                    <x-website.faq-list :faqs="$grouped['']->all()" />
                @endif
            @endif
        </section>

        @if ($articles !== [])
            <section class="mt-14" aria-labelledby="articles-title">
                <h2 id="articles-title" class="mb-5 text-2xl font-bold">{{ __('site.help.articles_title') }}</h2>
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach ($articles as $article)
                        <li>
                            <a class="block h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-brand-300" href="{{ $sitePaths->path('help-article', ['slug' => $article['slug']]) }}">
                                <h3 class="font-semibold text-slate-900">{{ $article['title'] }}</h3>
                                @if ($article['summary'])<p class="mt-1 text-sm leading-6 text-slate-600">{{ $article['summary'] }}</p>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="mt-14 rounded-2xl bg-slate-50 p-6">
            <h2 class="text-lg font-bold">{{ __('site.help.still_need') }}</h2>
            <p class="mt-1 text-slate-600">{{ __('site.help.still_need_text') }}</p>
            <a class="mt-4 inline-flex rounded-lg bg-brand-700 px-5 py-2.5 font-semibold text-white hover:bg-brand-800" href="{{ $sitePaths->path('contact') }}">{{ __('site.nav.contact') }}</a>
        </section>
    </div>
</x-website.layouts.site>
