@props(['name', 'label', 'placeholder' => null])
@php($id = 'filter-'.\App\Support\Admin\FieldName::id($name, null))
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <input type="search" id="{{ $id }}" name="filters[{{ $name }}]" value="{{ request()->input('filters.'.$name) }}" placeholder="{{ $placeholder }}" maxlength="191" {{ $attributes->class(['form-control']) }}>
</div>
