@props(['action', 'sending' => null, 'success' => null])
{{--
    Website form (P5.5): validate before submit. Bound by resources/js/website/site-form.js:
      checks in the browser -> server validation call (Precognition) -> inline errors and no submit, or the real AJAX submit.
    Works without JavaScript too (normal POST, errors from the session). Protection: CSRF, honeypot, time trap, throttling.
--}}
@php
    $guard = app(\App\Support\Website\FormGuard::class);
@endphp
<div data-site-form-wrap>
    <form
        action="{{ $action }}"
        method="POST"
        novalidate
        data-site-form
        data-msg-sending="{{ $sending ?? __('site.forms.sending') }}"
        data-msg-generic="{{ __('site.forms.errors.generic') }}"
        data-msg-throttled="{{ __('site.forms.errors.throttled') }}"
        data-msg-network="{{ __('site.forms.errors.network') }}"
        data-msg-expired="{{ __('site.forms.errors.session_expired') }}"
        data-msg-fix="{{ __('site.forms.fix_errors') }}"
        {{ $attributes->class(['space-y-5']) }}
    >
        @csrf
        <input type="hidden" name="{{ \App\Support\Website\FormGuard::TOKEN_FIELD }}" value="{{ $guard->token() }}">
        {{-- Honeypot: invisible to people and assistive technology; bots fill it. --}}
        <div class="absolute -start-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
            <label>{{ __('site.forms.honeypot') }}<input type="text" name="{{ \App\Support\Website\FormGuard::HONEYPOT }}" tabindex="-1" autocomplete="off" value=""></label>
        </div>

        <div data-error-summary tabindex="-1" role="alert" @if (! $errors->any()) hidden @endif class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">{{ __('site.forms.fix_errors') }}</p>
            <ul data-error-list class="mt-2 list-disc ps-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>

        {{ $slot }}
    </form>

    <section data-success-panel hidden tabindex="-1" class="rounded-xl border border-emerald-300 bg-emerald-50 p-6 text-emerald-900" aria-live="polite">
        {{ $success }}
    </section>
</div>
