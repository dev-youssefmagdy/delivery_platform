@props(['chart', 'type', 'title', 'id' => null, 'height' => '18rem', 'for' => null, 'href' => null, 'hrefLabel' => null])
{{--
    Shared frame of x-admin.chart.line|bar|donut (dataviz rules): title, an HTML legend (always for 2+ series, one entry per slice for a donut),
    a canvas with a text alternative, an "as table" view of the same numbers (hidden from sight but readable by assistive technology
    until toggled), and an empty state. `chart` is an App\DTOs\Reporting\ChartData (or its array); `for` binds the chart to a DataTable id so
    every filter reload redraws it (the table JSON carries `chart`). The drawing is done by resources/js/admin/core/charts.js.
--}}
@php
    $payload = $chart instanceof \App\DTOs\Reporting\ChartData ? $chart->toArray() : (array) $chart;
    $payload['type'] = $type;
    $id ??= 'chart-'.\Illuminate\Support\Str::random(6);
@endphp
<figure
    id="{{ $id }}"
    {{ $attributes->class(['admin-chart flex min-w-0 flex-col gap-3']) }}
    data-admin-chart
    @if ($for) data-chart-for="{{ $for }}" @endif
    data-i18n-series="{{ __('reporting.chart.series') }}"
    data-i18n-value="{{ __('reporting.chart.value') }}"
    data-i18n-total="{{ __('reporting.chart.total') }}"
    data-i18n-alt="{{ $title }}"
    x-data="{ table: false }"
>
    <figcaption class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-slate-500" data-chart-summary>{{ $payload['summary'] ?? '' }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            @if ($href)
                <a href="{{ $href }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50">
                    {{ $hrefLabel ?? __('reporting.dashboard.open_report') }}
                    <x-admin.icon name="arrow-right" class="size-3.5" />
                </a>
            @endif
            {{ $actions ?? '' }}
            <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100" x-on:click="table = !table" x-bind:aria-pressed="table ? 'true' : 'false'">
                {{ __('reporting.chart.table_view') }}
            </button>
        </div>
    </figcaption>

    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-700" data-chart-legend aria-label="{{ __('reporting.chart.series') }}"></ul>

    <div class="relative" style="height: {{ $height }}">
        <canvas data-chart="{{ json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) }}" role="img" aria-label="{{ $title }}"></canvas>
        <p class="absolute inset-0 hidden items-center justify-center text-sm text-slate-500" data-chart-empty>{{ __('reporting.chart.no_data') }}</p>
    </div>

    <div x-bind:class="table ? 'max-h-72 overflow-auto rounded-lg border border-slate-200' : 'sr-only'" data-chart-table tabindex="0" role="region" aria-label="{{ $title }}"></div>
</figure>
