<x-website.layouts.site :meta="$meta" >
    <article class="mx-auto max-w-3xl px-4 py-10 sm:py-14">
        <a href="{{ $sitePaths->path($backKey) }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline"><span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ $backLabel }}</a>
        <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">{{ $article['title'] }}</h1>
        @if ($article['published_at'])
            <p class="mt-3 text-sm text-slate-500"><time datetime="{{ $article['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($article['published_at'])->translatedFormat('j F Y') }}</time></p>
        @endif
        @if ($article['summary'])<p class="mt-4 text-lg leading-8 text-slate-600">{{ $article['summary'] }}</p>@endif
        @if ($article['cover_image'])
            <div class="mt-8"><x-website.image :image="$article['cover_image']" :eager="true" class="rounded-2xl" /></div>
        @endif
        <div class="prose-site mt-8">{!! $article['body_html'] !!}</div>
    </article>
</x-website.layouts.site>
