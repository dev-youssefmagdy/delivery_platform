@php($type = \App\Enums\Content\ContentType::Article)
<x-admin.layouts.app :title="__('content.articles.show_title')">
    <x-admin.page-header
        :title="$article->title"
        :breadcrumbs="[['label' => __('content.articles.title'), 'url' => route('admin.content-articles.index')], ['label' => $article->title]]"
    >
        <x-admin.content.actions :content="$article" :type="$type" />
    </x-admin.page-header>

    <x-admin.content.tabs :content="$article" :type="$type">
        <x-admin.detail-list.item :label="__('content.fields.article_type')" :value="$article->type->label()" />
        <x-admin.detail-list.item :label="__('content.fields.slug')" :value="$article->slug" />
        <x-admin.detail-list.item :label="__('content.fields.category')" :value="$article->category?->name" />
        <x-admin.detail-list.item :label="__('content.fields.title')" :value="$article->getTranslation('title', 'ar', false)" />
        <x-admin.detail-list.item :label="__('content.fields.title').' (EN)'" :value="$article->getTranslation('title', 'en', false)" />
        <x-admin.detail-list.item :label="__('content.fields.fallback_to_ar')" :value="$article->fallback_to_ar ? __('admin.common.yes') : __('admin.common.no')" />
        <x-admin.detail-list.item :label="__('content.fields.noindex')" :value="$article->noindex ? __('admin.common.yes') : __('admin.common.no')" />
    </x-admin.content.tabs>
</x-admin.layouts.app>
