@props(['timezones' => [], 'currencies' => [], 'cities' => true, 'zones' => false, 'categories' => false, 'grouping' => false])
{{-- Shared filter inputs of the dashboard and the report pages: market-day range, timezone, currency, city / zone / category, grouping. --}}
<x-admin.filters.date-range name="range" :label="__('reporting.filters.range')" />
<x-admin.filters.select name="timezone" :label="__('reporting.filters.timezone')" :options="array_combine($timezones, $timezones)" :placeholder="__('reporting.filters.all_markets')" />
@if ($currencies !== [])
    <x-admin.filters.select name="currency" :label="__('reporting.filters.currency')" :options="array_combine($currencies, $currencies)" />
@endif
@if ($grouping)
    <x-admin.filters.select name="group_by" :label="__('reporting.filters.group_by')" :options="\App\Enums\Reporting\ReportGrouping::options()" :placeholder="__('reporting.grouping.date')" />
@endif
@if ($cities)
    <x-admin.filters.remote-select name="city_ids" :label="__('reporting.filters.city')" lookup="cities" multiple />
@endif
@if ($zones)
    <x-admin.filters.remote-select name="zone_ids" :label="__('reporting.filters.zone')" lookup="zones" multiple />
@endif
@if ($categories)
    <x-admin.filters.remote-select name="category_ids" :label="__('reporting.filters.category')" lookup="service-categories" multiple />
@endif
