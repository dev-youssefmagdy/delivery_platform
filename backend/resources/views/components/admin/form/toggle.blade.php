@props(['name', 'label', 'hint' => null, 'checked' => false, 'id' => null])
{{-- Switch-style boolean (submits 1/0). --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $isChecked = (bool) \App\Support\Admin\FieldName::value($name, $checked);
@endphp
<x-admin.form.field :name="$name" :id="$id">
    <div class="flex items-center justify-between gap-4">
        <label for="{{ $id }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
        <span class="relative inline-flex">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" role="switch" name="{{ $name }}" id="{{ $id }}" value="1" @checked($isChecked) class="peer sr-only" @if ($hint) aria-describedby="{{ $id }}-hint" @endif {{ $attributes }}>
            <label for="{{ $id }}" class="toggle" aria-hidden="true"></label>
        </span>
    </div>
    @if ($hint)
        <p class="form-hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
</x-admin.form.field>
