@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'id' => null, 'rows' => 4, 'wrapperClass' => null])
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$id" :required="$required" :class="$wrapperClass">
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" @if ($hint) aria-describedby="{{ $id }}-hint" @endif {{ $attributes->class(['form-control']) }}>{{ \App\Support\Admin\FieldName::value($name, $value) }}</textarea>
</x-admin.form.field>
