{{-- Shared create/edit fields. $model: ?\App\Models\ServiceFamily --}}
<x-admin.form.section :title="__('admin.service_families.sections.identity')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="code_display" :label="__('admin.catalog.fields.code')" :value="$model->code" :hint="__('admin.catalog.hints.code')" dir="ltr" disabled />
        @else
            <x-admin.form.input name="code" :label="__('admin.catalog.fields.code')" :hint="__('admin.catalog.hints.code')" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" dir="ltr" required />
        @endif
        <x-admin.form.enum-select name="kind" :label="__('admin.service_families.fields.kind')" :enum="\App\Enums\Catalog\ServiceFamilyKind::class" :value="$model?->kind?->value" required />
    </x-admin.grid>
    <x-admin.form.translatable-input name="name" :label="__('admin.service_families.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
    <x-admin.form.translatable-textarea name="description" :label="__('admin.service_families.fields.description')" :value="$model?->getTranslations('description') ?? []" maxlength="1000" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.service_families.sections.presentation')">
    <x-admin.grid>
        <x-admin.form.input name="icon" :label="__('admin.catalog.fields.icon')" :value="$model?->icon" :hint="__('admin.catalog.hints.icon')" maxlength="64" dir="ltr" />
        <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('admin.catalog.fields.sort_order')" :value="$model?->sort_order ?? 0" :hint="__('admin.catalog.hints.sort_order')" />
    </x-admin.grid>
</x-admin.form.section>
