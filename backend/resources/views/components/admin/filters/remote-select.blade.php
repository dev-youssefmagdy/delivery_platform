@props(['name', 'label', 'lookup', 'multiple' => false, 'placeholder' => null])
{{-- Lookup-backed filter. Selected ids from the URL get their labels from the lookup (ids[]) on load. --}}
@php
    $id = 'filter-'.\App\Support\Admin\FieldName::id($name, null);
    $current = request()->input('filters.'.$name);
    $ids = array_values(array_filter(array_map('strval', is_array($current) ? $current : ($current === null ? [] : [$current]))));
    $selected = [];
    if ($ids !== [] && app(\App\Support\Admin\Lookups\LookupRegistry::class)->has($lookup)) {
        foreach (app(\App\Support\Admin\Lookups\LookupRegistry::class)->get($lookup)->resolve($ids) as $option) {
            $selected[(string) $option->id] = $option->text;
        }
    }
@endphp
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <select
        id="{{ $id }}"
        name="filters[{{ $name }}]{{ $multiple ? '[]' : '' }}"
        @if ($multiple) multiple @endif
        data-remote-select
        data-lookup-url="{{ route('admin.lookups', $lookup) }}"
        data-placeholder="{{ $placeholder ?? __('admin.common.any') }}"
        {{ $attributes->class(['form-control']) }}
    >
        @if (! $multiple)
            <option value=""></option>
        @endif
        @foreach ($selected as $value => $optionLabel)
            <option value="{{ $value }}" selected>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
