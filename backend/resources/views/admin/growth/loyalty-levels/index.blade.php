@php($table = \App\DataTables\Admin\Growth\LoyaltyLevelsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('growth.loyalty.title')">
    <x-admin.page-header
        :title="__('growth.loyalty.title')"
        :subtitle="__('growth.loyalty.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('growth.loyalty.title')]]"
    >
        @can('create', \App\Models\LoyaltyLevel::class)
            <x-admin.button :href="route('admin.loyalty-levels.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.boolean name="active" :label="__('growth.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
