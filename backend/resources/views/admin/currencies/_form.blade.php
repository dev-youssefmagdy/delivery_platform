{{-- Shared create/edit fields. $model: ?\App\Models\Currency --}}
<x-admin.form.section :title="__('admin.currencies.sections.definition')" :description="__('admin.currencies.sections.definition_hint')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="code_display" :label="__('admin.currencies.fields.code')" :value="$model->code" :hint="__('admin.currencies.hints.immutable')" dir="ltr" disabled />
            <x-admin.form.input name="exponent_display" :label="__('admin.currencies.fields.exponent')" :value="$model->exponent" :hint="__('admin.currencies.hints.immutable')" dir="ltr" disabled />
        @else
            <x-admin.form.input name="code" :label="__('admin.currencies.fields.code')" :hint="__('admin.currencies.hints.code')" maxlength="3" minlength="3" pattern="[A-Za-z]{3}" dir="ltr" required />
            <x-admin.form.input type="number" name="exponent" :label="__('admin.currencies.fields.exponent')" :value="2" :hint="__('admin.currencies.hints.exponent')" min="0" max="4" step="1" dir="ltr" required />
        @endif
        <x-admin.form.input name="symbol_ar" :label="__('admin.currencies.fields.symbol_ar')" :value="$model?->symbol_ar" maxlength="16" required />
        <x-admin.form.input name="symbol_en" :label="__('admin.currencies.fields.symbol_en')" :value="$model?->symbol_en" maxlength="16" dir="ltr" required />
    </x-admin.grid>
    <x-admin.form.toggle name="active" :label="__('admin.currencies.fields.active')" :hint="__('admin.currencies.hints.active')" :checked="$model?->active ?? true" />
</x-admin.form.section>
