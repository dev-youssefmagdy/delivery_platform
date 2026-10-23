<x-admin.layouts.app :title="__('content.banners.title')">
    <x-admin.page-header
        :title="__('content.banners.title')"
        :subtitle="__('content.banners.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.banners.title')]]"
    >
        @can('create', \App\Models\ContentBanner::class)
            <x-admin.button :href="route('admin.content-banners.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentBannersDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('content.banners.search_placeholder')" />
        <x-admin.filters.enum name="placements" :label="__('content.fields.placement')" :enum="\App\Enums\Content\BannerPlacement::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('content.fields.status')" :enum="\App\Enums\Content\ContentWorkflowStatus::class" multiple />
        <x-admin.filters.enum name="completeness" :label="__('content.fields.locales')" :enum="\App\Enums\Content\LocaleCompleteness::class" />
        <x-admin.filters.date-range name="updated_at" :label="__('admin.common.updated_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
