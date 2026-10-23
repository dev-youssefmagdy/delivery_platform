@php($table = \App\DataTables\Admin\Reporting\ReportsDataTable::TABLE_ID)
<x-admin.layouts.app :title="$report->label()">
    <x-admin.page-header
        :title="$report->label()"
        :subtitle="$report->description()"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('reporting.hub.title'), 'url' => route('admin.reports.index')], ['label' => $report->label()]]"
    >
        @if (! $report->isPlaceholder())
            @can('export', $report)
                <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.reports.export', $report->value)" data-export-table="{{ $table }}">{{ __('reporting.report.export') }}</x-admin.button>
            @endcan
        @endif
    </x-admin.page-header>

    @if ($report->isPlaceholder())
        <x-admin.empty-state icon="clock" :title="__('reporting.report.placeholder_title')" :description="__('reporting.report.placeholder_text')" />
    @else
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <x-admin.freshness :live="$report->freshness() === \App\Enums\Reporting\DataFreshness::Live" :at="$asOf" />
        </div>

        <x-admin.alert type="info" class="mb-6">
            <p class="font-medium">{{ __('reporting.report.definition_title') }}</p>
            <p class="mt-1">{{ __('reporting.reports.'.$report->value.'.definition') }}</p>
            <p class="mt-1">{{ $report->freshness() === \App\Enums\Reporting\DataFreshness::Live ? __('reporting.report.freshness_live') : __('reporting.report.freshness_aggregated') }} {{ $report->usesDateRange() ? __('reporting.report.timezone_note') : '' }}</p>
            @if ($report->hasPiiColumns())
                <p class="mt-1 font-medium">{{ $includesPii ? __('reporting.report.pii_shown') : __('reporting.report.pii_note') }}</p>
            @endif
        </x-admin.alert>

        <x-admin.filters-card for="{{ $table }}">
            @if ($report->usesDateRange())
                <x-admin.report.range-fields :timezones="$timezones" :currencies="in_array(\App\Enums\Reporting\ReportColumnType::Money, array_map(fn ($c) => $c->type, $report->columns()), true) ? $currencies : []" :cities="$report->hasCities()" :zones="$report->hasZones()" :categories="$report->hasCategories()" :grouping="$report->isGroupable()" />
            @else
                <x-admin.filters.select name="currency" :label="__('reporting.filters.currency')" :options="array_combine($currencies, $currencies)" />
            @endif
        </x-admin.filters-card>

        <x-admin.card class="mb-6">
            @if ($chart->type === 'line')
                <x-admin.chart.line :chart="$chart" :title="$report->label()" id="report-chart" :for="$table" />
            @else
                <x-admin.chart.bar :chart="$chart" :title="$report->label()" id="report-chart" :for="$table" />
            @endif
        </x-admin.card>

        <x-admin.card :title="__('reporting.report.table_title')" :padding="false" class="p-4">
            <x-admin.datatable :table="$dataTable" />
        </x-admin.card>
    @endif
</x-admin.layouts.app>
