@props(['name', 'label' => null, 'hint' => null, 'options' => [], 'value' => null, 'id' => null, 'placeholder' => null, 'multiple' => false, 'searchable' => false, 'wrapperClass' => null])
{{-- options: [value => label] or [group label => [value => label]]. Multiple selects need a name ending in []. --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
    $current = \App\Support\Admin\FieldName::value($name, $value);
    $selected = array_map(
        static fn ($item): string => (string) ($item instanceof \BackedEnum ? $item->value : $item),
        is_array($current) ? $current : ($current === null ? [] : [$current]),
    );
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$id" :required="$required" :class="$wrapperClass">
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($multiple) multiple @endif
        @if ($searchable || $multiple) data-enhanced-select data-placeholder="{{ $placeholder ?? __('admin.form.select_placeholder') }}" @endif
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class(['form-control']) }}
    >
        @if (! $multiple)
            <option value="">{{ $placeholder ?? __('admin.form.select_placeholder') }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @if (is_array($optionLabel))
                <optgroup label="{{ $optionValue }}">
                    @foreach ($optionLabel as $innerValue => $innerLabel)
                        <option value="{{ $innerValue }}" @selected(in_array((string) $innerValue, $selected, true))>{{ $innerLabel }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selected, true))>{{ $optionLabel }}</option>
            @endif
        @endforeach
    </select>
</x-admin.form.field>
