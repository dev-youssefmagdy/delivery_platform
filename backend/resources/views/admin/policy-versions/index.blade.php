<x-admin.layouts.app :title="__('admin.policy_versions.title')">
    <x-admin.page-header
        :title="__('admin.policy_versions.title')"
        :subtitle="__('admin.policy_versions.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.policy_versions.title')]]"
    >
        @can('create', \App\Models\PolicyVersion::class)
            <x-admin.button :href="route('admin.policy-versions.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="policy-versions-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.policy_versions.search_placeholder')" />
        <x-admin.filters.enum name="types" :label="__('admin.policy_versions.fields.type')" :enum="\App\Enums\Policy\PolicyType::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('admin.policy_versions.fields.status')" :enum="\App\Enums\Content\ContentStatus::class" multiple />
        <x-admin.filters.date-range name="published_at" :label="__('admin.policy_versions.fields.published_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
