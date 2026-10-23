@props(['name', 'label', 'suffix' => null, 'min' => 1, 'max' => null])
{{-- A single number filter (filters[name]). --}}
@php($id = 'filter-'.\App\Support\Admin\FieldName::id($name, null))
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if ($suffix) <span class="text-slate-500">({{ $suffix }})</span>@endif</label>
    <input type="number" id="{{ $id }}" name="filters[{{ $name }}]" value="{{ request()->input('filters.'.$name) }}" min="{{ $min }}" @if ($max !== null) max="{{ $max }}" @endif dir="ltr" {{ $attributes->class(['form-control']) }}>
</div>
