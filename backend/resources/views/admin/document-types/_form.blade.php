{{-- Shared create/edit fields. $model: ?\App\Models\DocumentType --}}
@php
    $mimeOptions = [];
    foreach (array_keys((array) config('provider.documents.mime_extensions', [])) as $mime) {
        $mimeOptions[$mime] = $mime;
    }
@endphp
<x-admin.form.section :title="__('admin.document_types.sections.identity')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="code_display" :label="__('admin.catalog.fields.code')" :value="$model->code" :hint="__('admin.catalog.hints.code')" dir="ltr" disabled />
        @else
            <x-admin.form.input name="code" :label="__('admin.catalog.fields.code')" :hint="__('admin.catalog.hints.code')" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" dir="ltr" required />
        @endif
        <x-admin.form.enum-select name="applies_to" :label="__('admin.document_types.fields.applies_to')" :enum="\App\Enums\Provider\DocumentSubject::class" :value="$model?->applies_to" required />
        <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('admin.catalog.fields.sort_order')" :value="$model?->sort_order ?? 0" :hint="__('admin.catalog.hints.sort_order')" />
    </x-admin.grid>
    <x-admin.form.translatable-input name="name" :label="__('admin.document_types.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
    <x-admin.form.translatable-textarea name="description" :label="__('admin.document_types.fields.description')" :value="$model?->getTranslations('description') ?? []" maxlength="1000" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.document_types.sections.rules')" :description="__('admin.document_types.sections.rules_hint')">
    <x-admin.grid>
        <x-admin.form.toggle name="requires_expiry" :label="__('admin.document_types.fields.requires_expiry')" :hint="__('admin.document_types.hints.requires_expiry')" :checked="$model?->requires_expiry ?? false" />
        <x-admin.form.toggle name="requires_number" :label="__('admin.document_types.fields.requires_number')" :hint="__('admin.document_types.hints.requires_number')" :checked="$model?->requires_number ?? false" />
        <x-admin.form.select name="allowed_mimes[]" :label="__('admin.document_types.fields.allowed_mimes')" :options="$mimeOptions" :value="$model?->allowed_mimes ?? ['image/jpeg', 'image/png']" :hint="__('admin.document_types.hints.allowed_mimes')" multiple required />
        <x-admin.form.input name="max_size_kb" type="number" min="100" :max="config('provider.documents.max_kilobytes')" :label="__('admin.document_types.fields.max_size_kb')" :value="$model?->max_size_kb ?? 5120" required />
    </x-admin.grid>
</x-admin.form.section>
