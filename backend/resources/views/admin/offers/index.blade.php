<x-admin.layouts.app :title="__('admin.offers.title')">
    <x-admin.page-header
        :title="__('admin.offers.title')"
        :subtitle="__('admin.offers.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.offers.title')]]"
    />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5" data-stats-for="{{ \App\DataTables\Admin\Booking\OffersMonitorDataTable::TABLE_ID }}">
        <x-admin.stat-tile stat="submitted" :label="__('admin.offers.stats.submitted')" :value="number_format($stats->submitted)" icon="tag" />
        <x-admin.stat-tile stat="selected" :label="__('admin.offers.stats.selected')" :value="number_format($stats->selected)" icon="check-circle" tone="success" />
        <x-admin.stat-tile stat="conversion" :label="__('admin.offers.stats.conversion')" :value="$stats->conversionPercent().'%'" :hint="__('admin.offers.stats.conversion_hint')" icon="chart" tone="info" />
        <x-admin.stat-tile stat="response" :label="__('admin.offers.stats.response')" :value="$stats->responsePercent().'%'" :hint="__('admin.offers.stats.response_hint')" icon="bolt" tone="neutral" />
        <x-admin.stat-tile stat="flagged" :label="__('admin.offers.stats.flagged')" :value="number_format($stats->flagged)" :hint="__('admin.offers.stats.flagged_hint')" icon="exclamation" tone="warning" />
    </div>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Booking\OffersMonitorDataTable::TABLE_ID }}">
        <x-admin.filters.text name="booking_code" :label="__('admin.offers.fields.booking')" />
        <x-admin.filters.remote-select name="provider_ids" :label="__('admin.offers.fields.provider')" lookup="providers" multiple />
        <x-admin.filters.enum name="statuses" :label="__('admin.offers.fields.status')" :enum="\App\Enums\Booking\OfferStatus::class" multiple />
        <x-admin.filters.enum name="kinds" :label="__('admin.offers.fields.kind')" :enum="\App\Enums\Booking\OfferKind::class" multiple />
        <x-admin.filters.number-range name="amount" :label="__('admin.offers.fields.amount')" min="0" />
        <x-admin.filters.enum name="anomaly" :label="__('admin.offers.fields.anomaly')" :enum="\App\Enums\Booking\OfferAnomaly::class" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.offers.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
