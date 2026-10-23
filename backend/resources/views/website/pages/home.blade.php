<x-website.layouts.site :meta="$meta">
    @if ($cmsPage && $cmsPage['blocks'] !== [])
        <x-website.blocks :blocks="$cmsPage['blocks']" :first-is-h1="true" />
    @else
        {{-- Built-in home (no published CMS home page): the propose-a-fare-and-choose model. --}}
        <section class="bg-gradient-to-br from-brand-800 to-brand-600 text-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
                <h1 class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">{{ __('site.home.title') }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-brand-50">{{ __('site.home.lede') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-website.cta-link :url="'/apps'" class="!bg-white !text-brand-800 hover:!bg-brand-50">{{ __('site.nav.get_app') }}</x-website.cta-link>
                    <x-website.cta-link :url="'/become-a-driver'" variant="outline">{{ __('site.nav.become-provider') }}</x-website.cta-link>
                </div>
            </div>
        </section>
        <section class="mx-auto max-w-6xl px-4 py-12">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ __('site.home.model_title') }}</h2>
            <ol class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['propose', 'offers', 'choose', 'ride'] as $step)
                    <li class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="mb-3 inline-flex size-9 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                        <h3 class="text-lg font-semibold">{{ __('site.home.steps.'.$step.'.title') }}</h3>
                        <p class="mt-2 leading-7 text-slate-600">{{ __('site.home.steps.'.$step.'.text') }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @foreach ($banners as $banner)
        <section class="mx-auto my-8 max-w-6xl px-4">
            <div class="rounded-2xl bg-accent-400/20 p-6 sm:p-8">
                <h2 class="text-xl font-bold text-slate-900">{{ $banner['title'] }}</h2>
                @if ($banner['body'])<p class="mt-2 whitespace-pre-line text-slate-700">{{ $banner['body'] }}</p>@endif
                @if ($banner['cta'])<div class="mt-4"><x-website.cta-link :url="$banner['cta']['url']">{{ $banner['cta']['label'] }}</x-website.cta-link></div>@endif
            </div>
        </section>
    @endforeach

    <section class="mx-auto max-w-6xl px-4 py-6">
        <p class="rounded-xl bg-slate-50 p-5 text-sm leading-7 text-slate-600">{{ __('site.home.fare_note') }}</p>
    </section>
</x-website.layouts.site>
