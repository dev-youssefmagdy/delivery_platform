@props(['name', 'label' => null, 'hint' => null, 'value' => [], 'required' => false, 'maxlength' => null, 'textarea' => false, 'rows' => 3])
{{--
    One input per supported locale in ar/en tabs; Arabic (default locale) is required when `required`.
    Submits name[ar], name[en]; errors title.ar / title.en appear in their tab (the tab gets an error dot).
--}}
@php
    $values = is_array($value) ? $value : [];
    $base = \App\Support\Admin\FieldName::id($name);
    $default = \App\Enums\Common\Locale::default();
@endphp
<fieldset {{ $attributes->only('class')->class(['min-w-0']) }} data-field="{{ \App\Support\Admin\FieldName::key($name) }}">
    @if ($label)
        <legend class="form-label">{{ $label }}@if ($required)<span class="form-required" aria-hidden="true"> *</span>@endif</legend>
    @endif

    <x-admin.tabs :tabs="collect(\App\Enums\Common\Locale::cases())->mapWithKeys(fn ($locale) => [$base.'-'.$locale->value => $locale->label()])->all()" :label="$label" class="[&_.tab-list]:border-b-0 [&>div:last-child]:pt-2">
        @foreach (\App\Enums\Common\Locale::cases() as $locale)
            @php
                $fieldName = $name.'['.$locale->value.']';
                $isRequired = $required && $locale === $default;
            @endphp
            <x-admin.tabs.panel :id="$base.'-'.$locale->value">
                @if ($textarea)
                    <x-admin.form.textarea :name="$fieldName" :value="$values[$locale->value] ?? null" :rows="$rows" :required="$isRequired" :maxlength="$maxlength" :lang="$locale->value" :dir="$locale->direction()" :aria-label="($label ?? $name).' ('.$locale->label().')'" />
                @else
                    <x-admin.form.input :name="$fieldName" :value="$values[$locale->value] ?? null" :required="$isRequired" :maxlength="$maxlength" :lang="$locale->value" :dir="$locale->direction()" :aria-label="($label ?? $name).' ('.$locale->label().')'" />
                @endif
            </x-admin.tabs.panel>
        @endforeach
    </x-admin.tabs>

    @if ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    <x-admin.form.field-error :for="\App\Support\Admin\FieldName::key($name)" />
</fieldset>
