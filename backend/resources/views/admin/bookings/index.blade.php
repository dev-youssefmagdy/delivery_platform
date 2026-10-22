<x-admin.layouts.app :title="__('admin.bookings.title')">
    <x-admin.page-header
        :title="__('admin.bookings.title')"
        :subtitle="__('admin.bookings.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.bookings.title')]]"
    >
        <x-admin.live-refresh :table="\App\DataTables\Admin\Booking\BookingsDataTable::TABLE_ID" />
        <x-admin.realtime-status />
        <x-admin.button variant="secondary" icon="clock" :href="route('admin.bookings.no-offer-queue')">{{ __('admin.bookings.queue.menu') }}</x-admin.button>
        <x-admin.button variant="secondary" icon="map" :href="route('admin.operations.map')">{{ __('admin.operations.menu') }}</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Booking\BookingsDataTable::TABLE_ID }}">
        <x-admin.filters.text name="code" :label="__('admin.bookings.fields.code')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.bookings.fields.status')" :enum="\App\Enums\Booking\BookingStatus::class" multiple />
        <x-admin.filters.remote-select name="service_category_ids" :label="__('admin.bookings.fields.service')" lookup="service-categories" multiple />
        <x-admin.filters.enum name="booking_types" :label="__('admin.bookings.fields.booking_type')" :enum="\App\Enums\Catalog\BookingType::class" multiple />
        <x-admin.filters.remote-select name="city_ids" :label="__('admin.bookings.fields.city')" lookup="cities" multiple />
        <x-admin.filters.remote-select name="zone_ids" :label="__('admin.bookings.fields.zone')" lookup="zones" multiple />
        <x-admin.filters.enum name="payment_statuses" :label="__('admin.bookings.fields.payment_status')" :enum="\App\Enums\Payments\PaymentStatus::class" multiple />
        <x-admin.filters.enum name="payment_methods" :label="__('admin.bookings.fields.payment_method')" :enum="\App\Enums\Payments\PaymentMethodType::class" multiple />
        <x-admin.filters.date-range name="created_at" :label="__('admin.bookings.fields.created')" />
        <x-admin.filters.text name="customer_phone" :label="__('admin.bookings.filters.customer_phone')" :placeholder="__('admin.bookings.filters.phone_hint')" />
        <x-admin.filters.text name="provider_phone" :label="__('admin.bookings.filters.provider_phone')" :placeholder="__('admin.bookings.filters.phone_hint')" />
        <x-admin.filters.boolean name="has_incident" :label="__('admin.bookings.filters.has_incident')" />
        <x-admin.filters.boolean name="has_ticket" :label="__('admin.bookings.filters.has_ticket')" />
        <x-admin.filters.enum name="custody_statuses" :label="__('admin.bookings.filters.custody_status')" :enum="\App\Enums\Booking\CustodyStatus::class" multiple />
        <x-admin.filters.enum name="parcel_sizes" :label="__('admin.bookings.filters.parcel_size')" :enum="\App\Enums\Booking\ParcelSize::class" multiple />
        <x-admin.filters.enum name="cargo_types" :label="__('admin.bookings.filters.cargo_type')" :enum="\App\Enums\Booking\CargoType::class" multiple />
        <x-admin.filters.remote-select name="vehicle_category_ids" :label="__('admin.bookings.filters.vehicle_class')" lookup="vehicle-categories" multiple />
        <x-admin.filters.number-range name="cargo_weight_kg" :label="__('admin.bookings.filters.cargo_weight')" suffix="kg" step="1" min="0" />
        <x-admin.filters.boolean name="is_fragile" :label="__('admin.bookings.filters.is_fragile')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
