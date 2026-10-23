@php($table = \App\DataTables\Admin\System\AppVersionsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('system.app_versions.title')">
    <x-admin.page-header
        :title="__('system.app_versions.title')"
        :subtitle="__('system.app_versions.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('system.app_versions.title')]]"
    >
        @can('create', \App\Models\AppVersion::class)
            <x-admin.button :href="route('admin.app-versions.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('system.app_versions.notice') }}</x-admin.alert>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.enum name="apps" :label="__('system.app_versions.fields.app')" :enum="\App\Enums\System\AppTarget::class" multiple />
        <x-admin.filters.enum name="platforms" :label="__('system.app_versions.fields.platform')" :enum="\App\Enums\System\AppPlatform::class" multiple />
        <x-admin.filters.boolean name="force_update" :label="__('system.app_versions.fields.force_update')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
