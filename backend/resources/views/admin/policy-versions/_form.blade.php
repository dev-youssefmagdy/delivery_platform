{{-- Shared create/edit fields for a policy draft. $model: ?\App\Models\PolicyVersion --}}
<x-admin.form.section :title="__('admin.policy_versions.sections.document')" :description="__('admin.policy_versions.sections.document_hint')">
    <x-admin.form.enum-select name="type" :label="__('admin.policy_versions.fields.type')" :enum="\App\Enums\Policy\PolicyType::class" :value="$model?->type" required />
    <x-admin.form.translatable-input name="title" :label="__('admin.policy_versions.fields.title')" :value="$model?->getTranslations('title') ?? []" maxlength="191" required />
    <x-admin.form.translatable-textarea name="content" :label="__('admin.policy_versions.fields.content')" :value="$model?->getTranslations('content') ?? []" :rows="16" maxlength="100000" required />
    <x-admin.form.textarea name="change_note" :label="__('admin.policy_versions.fields.change_note')" :hint="__('admin.policy_versions.hints.change_note')" :value="$model?->change_note" maxlength="2000" rows="3" />
</x-admin.form.section>
