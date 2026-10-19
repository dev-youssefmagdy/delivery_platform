<x-admin.layouts.app :title="__('admin.admins.title')">
    <x-admin.page-header
        :title="__('admin.admins.title')"
        :subtitle="__('admin.admins.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.admins.title')]]"
    >
        @can('create', \App\Models\Admin::class)
            <x-admin.button :href="route('admin.admins.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="admins-table">
        <x-admin.filters.text name="search" :label="__('admin.admins.filters.search')" />
        <x-admin.filters.select name="role" :label="__('admin.admins.fields.role')" :options="$roleOptions" />
        <x-admin.filters.enum name="statuses" :label="__('admin.admins.fields.status')" :enum="\App\Enums\Admin\AdminStatus::class" multiple />
        <x-admin.filters.boolean name="mfa_enabled" :label="__('admin.admins.fields.mfa')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
