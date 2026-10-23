@php($type = \App\Enums\Content\ContentType::Banner)
@php($tz = (string) config('admin.display_timezone'))
<x-admin.layouts.app :title="__('content.banners.show_title')">
    <x-admin.page-header
        :title="$banner->title"
        :breadcrumbs="[['label' => __('content.banners.title'), 'url' => route('admin.content-banners.index')], ['label' => $banner->title]]"
    >
        <x-admin.content.actions :content="$banner" :type="$type" />
    </x-admin.page-header>

    <x-admin.content.tabs :content="$banner" :type="$type">
        <x-admin.detail-list.item :label="__('content.fields.banner_kind')" :value="$banner->kind->label()" />
        <x-admin.detail-list.item :label="__('content.fields.placement')" :value="$banner->placement->label()" />
        <x-admin.detail-list.item :label="__('content.fields.audience')" :value="$banner->audience->label()" />
        <x-admin.detail-list.item :label="__('content.fields.title')" :value="$banner->getTranslation('title', 'ar', false)" />
        <x-admin.detail-list.item :label="__('content.fields.title').' (EN)'" :value="$banner->getTranslation('title', 'en', false)" />
        <x-admin.detail-list.item :label="__('content.fields.cta_url')" :value="$banner->cta_url" />
        <x-admin.detail-list.item :label="__('content.fields.starts_at')" :value="$banner->starts_at?->copy()->setTimezone($tz)->format('Y-m-d H:i')" />
        <x-admin.detail-list.item :label="__('content.fields.ends_at')" :value="$banner->ends_at?->copy()->setTimezone($tz)->format('Y-m-d H:i')" />
    </x-admin.content.tabs>
</x-admin.layouts.app>
