@props(['blocks', 'firstIsH1' => false])
{{--
    Sections of a CMS page. Only `body_html` is trusted HTML (sanitized when saved); every other string is escaped.
    Hero: title, body, CTA. Features / steps: items with title + plain text. Faq / cities / app_download are filled by the read service.
--}}
@foreach ($blocks as $index => $block)
    @php
        $type = $block['type'];
        $isHero = $type === 'hero';
        $heading = ($firstIsH1 && $index === 0) ? 'h1' : 'h2';
    @endphp
    @if ($isHero)
        <section class="bg-gradient-to-br from-brand-800 to-brand-600 text-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
                <{{ $heading }} class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">{{ $block['title'] }}</{{ $heading }}>
                @if ($block['body_html'])
                    <div class="prose-site prose-invert mt-5 max-w-2xl text-lg text-brand-50">{!! $block['body_html'] !!}</div>
                @endif
                @if ($block['cta'])
                    <div class="mt-8"><x-website.cta-link :url="$block['cta']['url']" class="!bg-white !text-brand-800 hover:!bg-brand-50">{{ $block['cta']['label'] }}</x-website.cta-link></div>
                @endif
            </div>
        </section>
    @elseif ($type === 'cta')
        <section class="mx-auto my-12 max-w-6xl px-4">
            <div class="rounded-2xl bg-brand-800 px-6 py-12 text-center text-white sm:px-12">
                <{{ $heading }} class="text-3xl font-bold">{{ $block['title'] }}</{{ $heading }}>
                @if ($block['body_html'])<div class="prose-site prose-invert mx-auto mt-4 max-w-2xl text-brand-50">{!! $block['body_html'] !!}</div>@endif
                @if ($block['cta'])<div class="mt-7"><x-website.cta-link :url="$block['cta']['url']" class="!bg-white !text-brand-800 hover:!bg-brand-50">{{ $block['cta']['label'] }}</x-website.cta-link></div>@endif
            </div>
        </section>
    @else
        <section class="mx-auto max-w-6xl px-4 py-10 sm:py-14">
            <{{ $heading }} class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ $block['title'] }}</{{ $heading }}>
            @if ($block['body_html'])
                <div class="prose-site mt-4 max-w-3xl">{!! $block['body_html'] !!}</div>
            @endif

            @if ($type === 'features' && $block['items'] !== [])
                <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($block['items'] as $item)
                        <li class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            @if ($item['text'])<p class="mt-2 whitespace-pre-line leading-7 text-slate-600">{{ $item['text'] }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @elseif ($type === 'steps' && $block['items'] !== [])
                <ol class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($block['items'] as $item)
                        <li class="relative rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <span class="mb-3 inline-flex size-9 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            @if ($item['text'])<p class="mt-2 whitespace-pre-line leading-7 text-slate-600">{{ $item['text'] }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            @elseif ($type === 'faq' && ! empty($block['faqs']))
                <div class="mt-8 max-w-3xl"><x-website.faq-list :faqs="$block['faqs']" /></div>
            @elseif ($type === 'cities' && ! empty($block['cities']))
                <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($block['cities'] as $city)
                        <li>
                            @if ($city['page_slug'])
                                <a class="block rounded-xl border border-slate-200 bg-white p-4 font-semibold text-brand-800 hover:border-brand-300 hover:bg-brand-50" href="{{ $sitePaths->path('city', ['slug' => $city['page_slug']]) }}">{{ $city['name'] }}</a>
                            @else
                                <span class="block rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-700">{{ $city['name'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif ($type === 'app_download')
                <div class="mt-6"><x-website.store-buttons :links="$block['app_links'] ?? []" app="customer" /></div>
                <p class="mt-4"><a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $sitePaths->path('apps') }}">{{ __('site.apps.all_links') }}</a></p>
            @endif

            @if ($block['cta'])
                <div class="mt-8"><x-website.cta-link :url="$block['cta']['url']">{{ $block['cta']['label'] }}</x-website.cta-link></div>
            @endif
        </section>
    @endif
@endforeach
