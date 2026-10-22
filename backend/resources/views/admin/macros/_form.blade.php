{{-- Shared create/edit fields. $model: ?\App\Models\SupportMacro --}}
<x-admin.form.section :title="__('admin.macros.sections.content')" :description="__('admin.macros.sections.content_hint')">
    <x-admin.form.translatable-input name="title" :label="__('admin.macros.fields.title')" :value="['ar' => $model?->title_ar, 'en' => $model?->title_en]" maxlength="120" required />
    <x-admin.form.translatable-textarea name="body" :label="__('admin.macros.fields.body')" :hint="__('admin.macros.hints.body')" :value="['ar' => $model?->body_ar, 'en' => $model?->body_en]" maxlength="2000" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.macros.sections.scope')" :description="__('admin.macros.sections.scope_hint')">
    <x-admin.grid>
        <x-admin.form.enum-select name="category" :label="__('admin.macros.fields.category')" :hint="__('admin.macros.hints.category')" :enum="\App\Enums\Support\TicketCategory::class" :value="$model?->category" :placeholder="__('admin.macros.all_categories')" />
        <x-admin.form.toggle name="active" :label="__('admin.macros.fields.active')" :checked="$model?->active ?? true" />
    </x-admin.grid>
</x-admin.form.section>
