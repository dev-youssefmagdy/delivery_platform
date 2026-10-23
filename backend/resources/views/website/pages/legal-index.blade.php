<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 sm:text-4xl">{{ __('site.legal.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ __('site.legal.lede') }}</p>
        </div>
    </header>
    <section class="mx-auto max-w-3xl px-4 py-10">
        @if ($documents === [])
            <p class="rounded-xl bg-slate-50 p-6 text-slate-700">{{ __('site.legal.empty') }}</p>
        @else
            <ul class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                @foreach ($documents as $document)
                    <li><a class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50" href="{{ $sitePaths->path('legal', ['type' => $document['type']]) }}"><span class="font-semibold text-slate-900">{{ $document['title'] }}</span><span class="text-sm text-slate-500">{{ __('site.legal.version', ['version' => $document['version']]) }}</span></a></li>
                @endforeach
            </ul>
        @endif
    </section>
</x-website.layouts.site>
