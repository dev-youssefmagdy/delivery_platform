@props(['name', 'label'])
{{-- A single date filter (filters[name], Y-m-d). --}}
@php($id = 'filter-'.\App\Support\Admin\FieldName::id($name, null))
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <div class="relative">
        <input
            type="text"
            id="{{ $id }}"
            name="filters[{{ $name }}]"
            value="{{ request()->input('filters.'.$name) }}"
            data-datepicker="date"
            autocomplete="off"
            dir="ltr"
            placeholder="YYYY-MM-DD"
            {{ $attributes->class(['form-control pe-9']) }}
        >
        <x-admin.icon name="calendar" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
    </div>
</div>
