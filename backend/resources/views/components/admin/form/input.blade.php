@props(['name', 'label' => null, 'hint' => null, 'type' => 'text', 'value' => null, 'id' => null, 'prefix' => null, 'suffix' => null, 'wrapperClass' => null])
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
    $current = $type === 'password' ? null : \App\Support\Admin\FieldName::value($name, $value);
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$id" :required="$required" :class="$wrapperClass">
    @if ($prefix || $suffix)
        <div class="input-group">
            @if ($prefix)<span class="input-addon">{{ $prefix }}</span>@endif
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($current !== null) value="{{ $current }}" @endif
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class(['form-control']) }}
    >
    @if ($prefix || $suffix)
            @if ($suffix)<span class="input-addon">{{ $suffix }}</span>@endif
        </div>
    @endif
</x-admin.form.field>
