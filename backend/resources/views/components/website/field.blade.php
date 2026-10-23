@props(['name', 'label', 'type' => 'text', 'hint' => null, 'required' => false, 'as' => 'input', 'value' => null])
{{--
    One form field: label, control, hint, and an inline error slot the form script fills (also filled from the session without JS).
    Per-field messages for the browser checks come from data-msg-* (so they are in the page language, not the browser's).
--}}
@php
    $id = 'f-'.$name;
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').$id.'-error');
    $current = old($name, $value);
@endphp
<div data-field="{{ $name }}">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold text-slate-800">
        {{ $label }}@if ($required)<span class="ms-1 text-red-600" aria-hidden="true">*</span><span class="sr-only"> ({{ __('site.forms.required') }})</span>@endif
    </label>
    @if ($as === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="5" aria-describedby="{{ $describedBy }}" @required($required)
            data-msg-required="{{ __('site.forms.client.required', ['field' => $label]) }}" data-msg-invalid="{{ __('site.forms.client.invalid', ['field' => $label]) }}"
            {{ $attributes->class(['block w-full rounded-lg border border-field bg-white px-3.5 py-2.5 text-base text-slate-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600 aria-[invalid=true]:border-red-600']) }}>{{ $current }}</textarea>
    @elseif ($as === 'select')
        <select id="{{ $id }}" name="{{ $name }}" aria-describedby="{{ $describedBy }}" @required($required)
            data-msg-required="{{ __('site.forms.client.required', ['field' => $label]) }}" data-msg-invalid="{{ __('site.forms.client.invalid', ['field' => $label]) }}"
            {{ $attributes->class(['block w-full rounded-lg border border-field bg-white px-3.5 py-2.5 text-base text-slate-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600 aria-[invalid=true]:border-red-600']) }}>
            {{ $slot }}
        </select>
    @elseif ($as === 'checkbox')
        <div class="flex items-start gap-3">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" @checked((bool) $current) aria-describedby="{{ $describedBy }}" @required($required)
                data-msg-required="{{ __('site.forms.client.required_check') }}" data-msg-invalid="{{ __('site.forms.client.required_check') }}"
                {{ $attributes->class(['mt-1 size-5 rounded border-slate-400 text-brand-700 focus:outline-2 focus:outline-brand-600']) }}>
            <div class="text-sm leading-6 text-slate-700">{{ $slot }}</div>
        </div>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" aria-describedby="{{ $describedBy }}" @required($required)
            data-msg-required="{{ __('site.forms.client.required', ['field' => $label]) }}" data-msg-invalid="{{ __('site.forms.client.invalid', ['field' => $label]) }}"
            {{ $attributes->class(['block w-full rounded-lg border border-field bg-white px-3.5 py-2.5 text-base text-slate-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600 aria-[invalid=true]:border-red-600']) }}>
    @endif
    @if ($hint)<p id="{{ $id }}-hint" class="mt-1.5 text-sm text-slate-500">{{ $hint }}</p>@endif
    <p id="{{ $id }}-error" data-error-for="{{ $name }}" class="mt-1.5 text-sm font-medium text-red-700" @if (! $error) hidden @endif>{{ $error }}</p>
</div>
