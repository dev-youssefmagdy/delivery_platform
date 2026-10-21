<x-admin.layouts.app :title="__('admin.bookings.queue.title')">
    <x-admin.page-header
        :title="__('admin.bookings.queue.title')"
        :subtitle="__('admin.bookings.queue.subtitle', ['seconds' => $afterSeconds, 'hours' => $expiredHours])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.bookings.title'), 'url' => route('admin.bookings.index')], ['label' => __('admin.bookings.queue.title')]]"
    >
        <x-admin.live-refresh :table="\App\DataTables\Admin\Booking\NoOfferQueueDataTable::TABLE_ID" :seconds="15" />
        <x-admin.realtime-status />
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('admin.bookings.queue.hint') }}</x-admin.alert>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
