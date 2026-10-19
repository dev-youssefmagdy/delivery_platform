{{-- Shared create/edit fields. $model: ?\App\Models\Role; $matrix: array{groups, selected} --}}
<x-admin.form.section :title="__('admin.roles.sections.basic')">
    @if (! $model)
        <x-admin.form.input name="name" :label="__('admin.roles.fields.name')" :hint="__('admin.roles.hints.name')" maxlength="64" required dir="ltr" />
    @elseif (! $model->is_system)
        <x-admin.form.input name="name" :label="__('admin.roles.fields.name')" :hint="__('admin.roles.hints.name')" :value="$model->name" maxlength="64" required dir="ltr" />
    @endif
    <x-admin.form.translatable-input name="label" :label="__('admin.roles.fields.label')" :value="$model?->getTranslations('label') ?? []" maxlength="191" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.roles.sections.permissions')" :description="__('admin.roles.sections.permissions_hint')" class="lg:grid-cols-1">
    <x-admin.permission-matrix :selected="$matrix['selected']" />
</x-admin.form.section>
