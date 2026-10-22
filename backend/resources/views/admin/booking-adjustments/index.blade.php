<x-admin.layouts.app :title="__('admin.booking_adjustments.title')">
    <x-admin.page-header
        :title="__('admin.booking_adjustments.title')"
        :subtitle="__('admin.booking_adjustments.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.booking_adjustments.title')]]"
    />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-tile :label="__('admin.booking_adjustments.stats.pending')" :value="number_format($pending)" icon="clock" tone="warning" />
    </div>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Booking\BookingAdjustmentsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.booking_adjustments.fields.status')" :enum="\App\Enums\Booking\BookingAdjustmentStatus::class" multiple />
        <x-admin.filters.enum name="types" :label="__('admin.booking_adjustments.fields.type')" :enum="\App\Enums\Booking\BookingAdjustmentType::class" multiple />
        <x-admin.filters.text name="search" :label="__('admin.booking_adjustments.filters.search')" :placeholder="__('admin.booking_adjustments.filters.search_hint')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.booking_adjustments.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
