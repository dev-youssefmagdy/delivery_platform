@props(['name', 'label', 'options' => [], 'multiple' => false, 'placeholder' => null])
@php
    $id = 'filter-'.\App\Support\Admin\FieldName::id($name, null);
    $current = request()->input('filters.'.$name);
    $selected = array_map('strval', is_array($current) ? $current : ($current === null ? [] : [$current]));
@endphp
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <select id="{{ $id }}" name="filters[{{ $name }}]{{ $multiple ? '[]' : '' }}" @if ($multiple) multiple data-enhanced-select data-placeholder="{{ $placeholder ?? __('admin.common.any') }}" @endif {{ $attributes->class(['form-control']) }}>
        @if (! $multiple)
            <option value="">{{ $placeholder ?? __('admin.common.any') }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected(in_array((string) $value, $selected, true))>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
