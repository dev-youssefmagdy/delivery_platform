@php($type = \App\Enums\Content\ContentType::Faq)
<x-admin.layouts.app :title="__('content.faqs.show_title')">
    <x-admin.page-header
        :title="$faq->question"
        :breadcrumbs="[['label' => __('content.faqs.title'), 'url' => route('admin.content-faqs.index')], ['label' => $faq->question]]"
    >
        <x-admin.content.actions :content="$faq" :type="$type" />
    </x-admin.page-header>

    <x-admin.content.tabs :content="$faq" :type="$type">
        <x-admin.detail-list.item :label="__('content.fields.category')" :value="$faq->category?->name" />
        <x-admin.detail-list.item :label="__('content.fields.service_category')" :value="$faq->serviceCategory?->name" />
        <x-admin.detail-list.item :label="__('content.fields.question')" :value="$faq->getTranslation('question', 'ar', false)" />
        <x-admin.detail-list.item :label="__('content.fields.question').' (EN)'" :value="$faq->getTranslation('question', 'en', false)" />
        <x-admin.detail-list.item :label="__('content.fields.sort_order')" :value="$faq->sort_order" />
        <x-admin.detail-list.item :label="__('content.fields.fallback_to_ar')" :value="$faq->fallback_to_ar ? __('admin.common.yes') : __('admin.common.no')" />
    </x-admin.content.tabs>
</x-admin.layouts.app>
