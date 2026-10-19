@props(['name', 'label', 'hint' => null, 'checked' => false, 'id' => null, 'value' => '1', 'uncheckedValue' => '0'])
{{-- Single boolean checkbox; a hidden input submits the unchecked value. --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $isChecked = (bool) \App\Support\Admin\FieldName::value($name, $checked);
@endphp
<x-admin.form.field :name="$name" :id="$id">
    <div class="flex items-start gap-3">
        @if ($uncheckedValue !== null)
            <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}">
        @endif
        <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" @checked($isChecked) @if ($hint) aria-describedby="{{ $id }}-hint" @endif {{ $attributes->class(['form-check mt-0.5']) }}>
        <label for="{{ $id }}" class="text-sm text-slate-700">{{ $label }}</label>
    </div>
    @if ($hint)
        <p class="form-hint ms-7" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
</x-admin.form.field>
