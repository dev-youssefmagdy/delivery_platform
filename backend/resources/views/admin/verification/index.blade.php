<x-admin.layouts.app :title="__('admin.verification.title')">
    <x-admin.page-header
        :title="__('admin.verification.title')"
        :subtitle="__('admin.verification.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.verification.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Provider\VerificationQueueDataTable::TABLE_ID }}">
        <x-admin.filters.select name="statuses" :label="__('admin.providers.fields.status')" :options="[\App\Enums\Provider\ProviderStatus::UnderReview->value => \App\Enums\Provider\ProviderStatus::UnderReview->label(), \App\Enums\Provider\ProviderStatus::MoreInformationRequired->value => \App\Enums\Provider\ProviderStatus::MoreInformationRequired->label()]" multiple />
        <x-admin.filters.enum name="provider_types" :label="__('admin.providers.fields.provider_type')" :enum="\App\Enums\Provider\ProviderType::class" multiple />
        <x-admin.filters.remote-select name="city_ids" :label="__('admin.providers.fields.city')" lookup="cities" multiple />
        <x-admin.filters.number-range-single name="waiting_hours" :label="__('admin.verification.filters.waiting_more_than')" :suffix="__('admin.verification.filters.hours')" />
        <x-admin.filters.remote-select name="assigned_admin_ids" :label="__('admin.verification.fields.assigned')" lookup="admins" multiple />
        <x-admin.filters.boolean name="unassigned" :label="__('admin.verification.filters.unassigned_only')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
