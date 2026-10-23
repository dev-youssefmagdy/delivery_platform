@php($title = $category ? __('content.categories.edit_title') : __('content.categories.create_title'))
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="[['label' => __('content.categories.title'), 'url' => route('admin.content-categories.index')], ['label' => $category ? __('admin.common.edit') : __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="$category ? route('admin.content-categories.update', $category) : route('admin.content-categories.store')" :method="$category ? 'PUT' : 'POST'">
            @if ($category)
                <input type="hidden" name="lock_version" value="{{ $category->lock_version }}">
                <input type="hidden" name="kind" value="{{ $category->kind->value }}">
            @endif
            <x-admin.form.section :title="__('content.sections.basics')">
                @unless ($category)
                    <x-admin.form.enum-select name="kind" :label="__('content.fields.category_kind')" :enum="\App\Enums\Content\CategoryKind::class" required />
                @endunless
                <x-admin.form.input name="slug" dir="ltr" :label="__('content.fields.slug')" :hint="__('content.hints.slug_category')" :value="$category?->slug" maxlength="96" required />
                <x-admin.form.translatable-input name="name" :label="__('content.fields.name')" :value="$category?->getTranslations('name') ?? []" maxlength="120" required />
                <x-admin.form.translatable-textarea name="description" :label="__('content.fields.description')" :value="$category?->getTranslations('description') ?? []" :rows="2" maxlength="500" />
                <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('content.fields.sort_order')" :hint="__('content.hints.sort_order')" :value="$category?->sort_order ?? 0" />
                <x-admin.form.toggle name="active" :label="__('content.fields.active')" :hint="__('content.hints.category_active')" :checked="$category?->active ?? true" />
            </x-admin.form.section>
            <x-admin.form.actions :cancel="route('admin.content-categories.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
