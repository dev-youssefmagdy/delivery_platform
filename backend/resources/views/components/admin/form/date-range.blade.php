@props(['label' => null, 'hint' => null, 'from', 'to', 'fromValue' => null, 'toValue' => null, 'required' => false])
{{-- Two linked date pickers (from ≤ to). `from` / `to` are the field names. --}}
@php
    $fromId = \App\Support\Admin\FieldName::id($from);
    $toId = \App\Support\Admin\FieldName::id($to);
@endphp
<fieldset {{ $attributes->class(['min-w-0']) }}>
    @if ($label)
        <legend class="form-label">{{ $label }}@if ($required)<span class="form-required" aria-hidden="true"> *</span>@endif</legend>
    @endif
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-admin.form.date :name="$from" :id="$fromId" :value="$fromValue" :required="$required" :placeholder="__('admin.common.from')" :aria-label="($label ? $label.' — ' : '').__('admin.common.from')" :data-range-end="$toId" />
        <x-admin.form.date :name="$to" :id="$toId" :value="$toValue" :required="$required" :placeholder="__('admin.common.to')" :aria-label="($label ? $label.' — ' : '').__('admin.common.to')" :data-range-start="$fromId" />
    </div>
    @if ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
</fieldset>
