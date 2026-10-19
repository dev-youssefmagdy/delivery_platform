@props(['name', 'label'])
{{-- Submits filters[name][from] / filters[name][to] (Y-m-d). --}}
@php
    $base = 'filter-'.\App\Support\Admin\FieldName::id($name, null);
@endphp
<fieldset class="min-w-0 sm:col-span-2">
    <legend class="form-label">{{ $label }}</legend>
    <div class="grid grid-cols-2 gap-2">
        @foreach (['from', 'to'] as $edge)
            <div class="relative">
                <input
                    type="text"
                    id="{{ $base }}-{{ $edge }}"
                    name="filters[{{ $name }}][{{ $edge }}]"
                    value="{{ request()->input('filters.'.$name.'.'.$edge) }}"
                    data-datepicker="date"
                    @if ($edge === 'from') data-range-end="{{ $base }}-to" @else data-range-start="{{ $base }}-from" @endif
                    placeholder="{{ __('admin.common.'.$edge) }}"
                    aria-label="{{ $label }} — {{ __('admin.common.'.$edge) }}"
                    autocomplete="off"
                    dir="ltr"
                    class="form-control pe-9"
                >
                <x-admin.icon name="calendar" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            </div>
        @endforeach
    </div>
</fieldset>
