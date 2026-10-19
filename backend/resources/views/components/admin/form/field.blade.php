@props(['name', 'label' => null, 'hint' => null, 'id' => null, 'required' => false, 'errorKey' => null, 'labelFor' => true])
{{-- Internal wrapper used by every form field: label, control slot, hint, error slot ([data-field] = error key). --}}
@php
    $key = $errorKey ?? \App\Support\Admin\FieldName::key($name);
    $id ??= \App\Support\Admin\FieldName::id($name);
@endphp
<div data-field="{{ $key }}" {{ $attributes->class(['min-w-0']) }}>
    @if ($label)
        @if ($labelFor)
            <label for="{{ $id }}" class="form-label">
        @else
            <span class="form-label" id="{{ $id }}-label">
        @endif
            {{ $label }}
            @if ($required)
                <span class="form-required" aria-hidden="true">*</span>
            @endif
        @if ($labelFor)
            </label>
        @else
            </span>
        @endif
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="form-hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif

    <x-admin.form.field-error :for="$key" />
</div>
