{{-- Become a provider: CMS recruitment content, then the lead form. NO file field: documents are collected later, inside the provider app. --}}
<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="max-w-3xl text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">{{ $cmsPage['title'] ?? __('site.provider.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ ($cmsPage['summary'] ?? null) ?: __('site.provider.lede') }}</p>
        </div>
    </header>

    @if ($cmsPage && $cmsPage['body_html'])
        <div class="mx-auto max-w-3xl px-4 py-10"><div class="prose-site">{!! $cmsPage['body_html'] !!}</div></div>
    @endif
    @if ($cmsPage && $cmsPage['blocks'] !== [])
        <x-website.blocks :blocks="$cmsPage['blocks']" />
    @endif

    <section id="apply" class="mx-auto max-w-2xl px-4 py-12" aria-labelledby="apply-title">
        <h2 id="apply-title" class="text-2xl font-bold text-slate-900">{{ __('site.provider.form_title') }}</h2>
        <p class="mt-2 text-slate-600">{{ __('site.provider.form_lede') }}</p>
        <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ __('site.provider.no_documents') }}</p>

        <div class="mt-6">
            @if ($reference)
                <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-900" aria-live="polite">
                    <h3 class="text-lg font-bold">{{ __('site.provider.success_title') }}</h3>
                    <p class="mt-2">{{ __('site.provider.success_message') }}</p>
                    <p class="mt-3">{{ __('site.forms.your_reference') }}: <strong class="ltr-isolate font-mono">{{ $reference }}</strong></p>
                </section>
            @else
                <x-website.form :action="$sitePaths->path('become-provider')">
                    <x-slot:success>
                        <h3 class="text-lg font-bold">{{ __('site.provider.success_title') }}</h3>
                        <p class="mt-2">{{ __('site.provider.success_message') }}</p>
                        <p class="mt-3">{{ __('site.forms.your_reference') }}: <strong class="ltr-isolate font-mono" data-reference></strong></p>
                    </x-slot:success>

                    <x-website.field name="name" :label="__('site.provider.fields.name')" required maxlength="120" autocomplete="name" />
                    <x-website.field name="phone" type="tel" :label="__('site.provider.fields.phone')" :hint="__('site.forms.phone_hint')" required maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr" />
                    <x-website.field name="email" type="email" :label="__('site.provider.fields.email')" maxlength="191" autocomplete="email" dir="ltr" />
                    <x-website.field name="city_id" as="select" :label="__('site.provider.fields.city')">
                        <option value="">{{ __('site.provider.other_city') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city['id'] }}" @selected((string) old('city_id') === (string) $city['id'])>{{ $city['name'] }}</option>
                        @endforeach
                    </x-website.field>
                    <x-website.field name="city_other" :label="__('site.provider.fields.city_other')" :hint="__('site.provider.city_other_hint')" maxlength="120" />
                    <x-website.field name="service_category_id" as="select" :label="__('site.provider.fields.service')">
                        <option value="">{{ __('site.provider.any_service') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_category_id') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </x-website.field>
                    <x-website.field name="vehicle_note" :label="__('site.provider.fields.vehicle')" :hint="__('site.provider.vehicle_hint')" maxlength="160" />
                    <x-website.field name="message" as="textarea" :label="__('site.provider.fields.message')" maxlength="1500" />
                    <x-website.field name="consent" as="checkbox" :label="__('site.forms.consent_label')" required>
                        {{ __('site.forms.consent_text') }}
                        @php($privacy = $sitePaths->path('legal', ['type' => 'privacy']))
                        <a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $privacy }}" target="_blank" rel="noopener">{{ __('site.forms.privacy_link') }}</a>
                    </x-website.field>

                    <x-website.submit :label="__('site.provider.submit')" />
                </x-website.form>
            @endif
        </div>
    </section>
</x-website.layouts.site>
