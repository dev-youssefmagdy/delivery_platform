<x-website.layouts.site :meta="$meta">
    <header class="bg-brand-50">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
            <h1 class="max-w-3xl text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">{{ $cmsPage['title'] ?? __('site.contact.title') }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">{{ ($cmsPage['summary'] ?? null) ?: __('site.contact.lede') }}</p>
        </div>
    </header>

    @if ($cmsPage && $cmsPage['body_html'])
        <div class="mx-auto max-w-3xl px-4 py-10"><div class="prose-site">{!! $cmsPage['body_html'] !!}</div></div>
    @endif

    <section class="mx-auto grid max-w-5xl gap-10 px-4 py-12 lg:grid-cols-3" aria-labelledby="contact-title">
        <div class="lg:col-span-2">
            <h2 id="contact-title" class="text-2xl font-bold text-slate-900">{{ __('site.contact.form_title') }}</h2>
            <p class="mt-2 text-slate-600">{{ __('site.contact.form_lede') }}</p>

            <div class="mt-6">
                @if ($reference)
                    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-900" aria-live="polite">
                        <h3 class="text-lg font-bold">{{ __('site.contact.success_title') }}</h3>
                        <p class="mt-2">{{ __('site.contact.success_message') }}</p>
                        <p class="mt-3">{{ __('site.forms.your_reference') }}: <strong class="ltr-isolate font-mono">{{ $reference }}</strong></p>
                    </section>
                @else
                    <x-website.form :action="$sitePaths->path('contact')">
                        <x-slot:success>
                            <h3 class="text-lg font-bold">{{ __('site.contact.success_title') }}</h3>
                            <p class="mt-2">{{ __('site.contact.success_message') }}</p>
                            <p class="mt-3">{{ __('site.forms.your_reference') }}: <strong class="ltr-isolate font-mono" data-reference></strong></p>
                        </x-slot:success>

                        <x-website.field name="name" :label="__('site.contact.fields.name')" required maxlength="120" autocomplete="name" />
                        <x-website.field name="email" type="email" :label="__('site.contact.fields.email')" required maxlength="191" autocomplete="email" dir="ltr" />
                        <x-website.field name="phone" type="tel" :label="__('site.contact.fields.phone')" :hint="__('site.forms.phone_hint')" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr" />
                        <x-website.field name="category" as="select" :label="__('site.contact.fields.category')" required>
                            <option value="">{{ __('site.contact.choose_category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </x-website.field>
                        <x-website.field name="subject" :label="__('site.contact.fields.subject')" required maxlength="160" />
                        <x-website.field name="message" as="textarea" :label="__('site.contact.fields.message')" :hint="__('site.contact.message_hint')" required maxlength="4000" />
                        <x-website.field name="consent" as="checkbox" :label="__('site.forms.consent_label')" required>
                            {{ __('site.forms.consent_text') }}
                            @php($privacy = $sitePaths->path('legal', ['type' => 'privacy']))
                            <a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $privacy }}" target="_blank" rel="noopener">{{ __('site.forms.privacy_link') }}</a>
                        </x-website.field>

                        <x-website.submit :label="__('site.contact.submit')" />
                    </x-website.form>
                @endif
            </div>
        </div>

        <aside class="space-y-5 text-sm leading-7 text-slate-700" aria-label="{{ __('site.contact.other_ways') }}">
            <h2 class="text-lg font-bold text-slate-900">{{ __('site.contact.other_ways') }}</h2>
            <p>{{ __('site.contact.in_app_help') }}</p>
            <p><a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ $sitePaths->path('help') }}">{{ __('site.contact.read_faq') }}</a></p>
            @if (config('website.contact.support_email'))
                <p>{{ __('site.contact.email_us') }}: <a dir="ltr" class="ltr-isolate font-semibold text-brand-700" href="mailto:{{ config('website.contact.support_email') }}">{{ config('website.contact.support_email') }}</a></p>
            @endif
            @if (config('website.contact.support_phone'))
                <p>{{ __('site.contact.call_us') }}: <a dir="ltr" class="ltr-isolate font-semibold text-brand-700" href="tel:{{ preg_replace('/[^+0-9]/', '', (string) config('website.contact.support_phone')) }}">{{ config('website.contact.support_phone') }}</a></p>
            @endif
            <p class="text-slate-500">{{ __('site.contact.emergency') }}</p>
        </aside>
    </section>
</x-website.layouts.site>
