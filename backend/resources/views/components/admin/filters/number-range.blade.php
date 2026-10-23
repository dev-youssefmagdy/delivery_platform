@props(['name', 'label', 'step' => 'any', 'min' => null, 'suffix' => null])
{{-- Submits filters[name][min] / filters[name][max]. --}}
@php($base = 'filter-'.\App\Support\Admin\FieldName::id($name, null))
<fieldset class="min-w-0">
    <legend class="form-label">{{ $label }}@if ($suffix) <span class="text-slate-500">({{ $suffix }})</span>@endif</legend>
    <div class="grid grid-cols-2 gap-2">
        @foreach (['min', 'max'] as $edge)
            <input
                type="number"
                id="{{ $base }}-{{ $edge }}"
                name="filters[{{ $name }}][{{ $edge }}]"
                value="{{ request()->input('filters.'.$name.'.'.$edge) }}"
                step="{{ $step }}"
                @if ($min !== null) min="{{ $min }}" @endif
                placeholder="{{ __('admin.common.'.$edge) }}"
                aria-label="{{ $label }} — {{ __('admin.common.'.$edge) }}"
                dir="ltr"
                class="form-control"
            >
        @endforeach
    </div>
</fieldset>
