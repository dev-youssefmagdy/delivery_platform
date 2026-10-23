{{-- Legal document from policy_versions: PLAIN TEXT. Escaped ({{ }}), line breaks kept by CSS (white-space: pre-line). --}}
<x-website.layouts.site :meta="$meta">
    <article class="mx-auto max-w-3xl px-4 py-10 sm:py-14">
        <a href="{{ $sitePaths->path('legal-index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline"><span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ __('site.legal.title') }}</a>
        <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">{{ $policy['title'] }}</h1>
        <p class="mt-3 text-sm text-slate-500">
            {{ __('site.legal.version', ['version' => $policy['version']]) }}
            @if ($policy['published_at'])
                &middot; {{ __('site.legal.effective') }} <time datetime="{{ $policy['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($policy['published_at'])->translatedFormat('j F Y') }}</time>
            @endif
        </p>
        <div class="legal-text mt-8 text-slate-800">{{ $policy['content'] }}</div>
    </article>
</x-website.layouts.site>
