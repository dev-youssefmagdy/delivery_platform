@php($type = \App\Enums\Content\ContentType::Page)
<x-admin.layouts.app :title="__('content.pages.show_title')">
    <x-admin.page-header
        :title="$page->title"
        :breadcrumbs="[['label' => __('content.pages.title'), 'url' => route('admin.content-pages.index')], ['label' => $page->title]]"
    >
        <x-admin.content.actions :content="$page" :type="$type" />
    </x-admin.page-header>

    <x-admin.content.tabs :content="$page" :type="$type">
        <x-admin.detail-list.item :label="__('content.fields.kind')" :value="$page->kind->label()" />
        <x-admin.detail-list.item :label="__('content.fields.slug')" :value="$page->slug" />
        <x-admin.detail-list.item :label="__('content.fields.title')" :value="$page->getTranslation('title', 'ar', false)" />
        <x-admin.detail-list.item :label="__('content.fields.title').' (EN)'" :value="$page->getTranslation('title', 'en', false)" />
        <x-admin.detail-list.item :label="__('content.fields.service_category')" :value="$page->serviceCategory?->name" />
        <x-admin.detail-list.item :label="__('content.fields.city')" :value="$page->city?->name" />
        <x-admin.detail-list.item :label="__('content.fields.blocks')" :value="count($page->blocks ?? [])" />
        <x-admin.detail-list.item :label="__('content.fields.fallback_to_ar')" :value="$page->fallback_to_ar ? __('admin.common.yes') : __('admin.common.no')" />
        <x-admin.detail-list.item :label="__('content.fields.noindex')" :value="$page->noindex ? __('admin.common.yes') : __('admin.common.no')" />
    </x-admin.content.tabs>
</x-admin.layouts.app>
