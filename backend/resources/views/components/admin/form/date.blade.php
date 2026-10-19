@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'id' => null, 'mode' => 'date', 'min' => null, 'max' => null, 'placeholder' => null, 'wrapperClass' => null])
{{-- flatpickr date input; submits Y-m-d (mode="datetime": Y-m-d H:i, mode="time": H:i). --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
    $format = ['datetime' => 'Y-m-d H:i', 'time' => 'H:i'][$mode] ?? 'Y-m-d';
    $current = \App\Support\Admin\FieldName::value($name, $value);
    $current = $current instanceof \DateTimeInterface
        ? \Carbon\CarbonImmutable::instance($current)->setTimezone((string) config('admin.display_timezone'))->format($format)
        : $current;
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$id" :required="$required" :class="$wrapperClass">
    <div class="relative">
        <input
            type="text"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $current }}"
            data-datepicker="{{ $mode }}"
            @if ($min) data-min-date="{{ $min }}" @endif
            @if ($max) data-max-date="{{ $max }}" @endif
            autocomplete="off"
            dir="ltr"
            placeholder="{{ $placeholder ?? ($format === 'H:i' ? 'HH:MM' : ($format === 'Y-m-d' ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:MM')) }}"
            @if ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->class(['form-control pe-9']) }}
        >
        <x-admin.icon :name="$mode === 'time' ? 'clock' : 'calendar'" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
    </div>
</x-admin.form.field>
