@props(['name', 'label', 'trueLabel' => null, 'falseLabel' => null])
{{-- Tri-state: any / yes / no (filters[name] = '' | 1 | 0). --}}
@php
    $current = (string) request()->input('filters.'.$name, '');
    $base = 'filter-'.\App\Support\Admin\FieldName::id($name, null);
    $choices = ['' => __('admin.common.any'), '1' => $trueLabel ?? __('admin.common.yes'), '0' => $falseLabel ?? __('admin.common.no')];
@endphp
<fieldset class="min-w-0">
    <legend class="form-label">{{ $label }}</legend>
    <div class="inline-flex rounded-(--radius-control) border border-field bg-white p-0.5" role="radiogroup">
        @foreach ($choices as $value => $choiceLabel)
            <label class="cursor-pointer">
                <input type="radio" name="filters[{{ $name }}]" value="{{ $value }}" id="{{ $base }}-{{ $value === '' ? 'any' : $value }}" class="peer sr-only" @checked($current === (string) $value)>
                <span class="inline-block rounded-[calc(var(--radius-control)-2px)] px-3 py-1.5 text-sm text-slate-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-brand-500">{{ $choiceLabel }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
