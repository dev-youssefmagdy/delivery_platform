@php($table = \App\DataTables\Admin\System\FeatureFlagsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('system.flags.title')">
    <x-admin.page-header
        :title="__('system.flags.title')"
        :subtitle="__('system.flags.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('system.flags.title')]]"
    >
        @can('create', \App\Models\FeatureFlag::class)
            <x-admin.button :href="route('admin.feature-flags.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('system.flags.notice') }}</x-admin.alert>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.text name="key" :label="__('system.flags.fields.key')" />
        <x-admin.filters.boolean name="enabled" :label="__('system.flags.fields.enabled')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
