@props(['tile'])
{{--
    KPI widget (SRS 16.1): value, comparison with the previous period, drill-down link, an info disclosure with the definition
    (numerator, denominator, time basis, freshness) and a live / aggregated marker. No personal data ever appears here.
--}}
@php
    $key = $tile->key;
    $tones = [
        'info' => 'bg-info-50 text-info-700', 'success' => 'bg-success-50 text-success-700', 'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700', 'primary' => 'bg-brand-50 text-brand-700',
    ];
    $id = 'kpi-'.$key->value;
    $live = $tile->freshness === \App\Enums\Reporting\DataFreshness::Live;
@endphp
<div {{ $attributes->class(['card relative flex flex-col gap-2 p-5']) }} x-data="{ info: false }" x-on:keydown.escape.window="info = false" x-on:click.outside="info = false" data-kpi="{{ $key->value }}">
    <div class="flex items-start gap-3">
        <span @class(['inline-flex size-10 shrink-0 items-center justify-center rounded-xl', $tones[$key->tone()] ?? $tones['info']])>
            <x-admin.icon :name="$key->icon()" class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm text-slate-600">{{ $key->label() }}</p>
            @if ($tile->href)
                <a href="{{ $tile->href }}" class="mt-0.5 block text-2xl font-semibold text-slate-900 tabular-nums hover:text-brand-700" title="{{ __('reporting.dashboard.open_listing') }}">{{ $tile->value }}<span class="sr-only"> — {{ __('reporting.dashboard.open_listing') }}</span></a>
            @else
                <p class="mt-0.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $tile->value }}</p>
            @endif
        </div>
        <button type="button" class="shrink-0 rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700" x-on:click="info = !info" x-bind:aria-expanded="info ? 'true' : 'false'" aria-controls="{{ $id }}-info" aria-label="{{ __('reporting.dashboard.info') }}">
            <x-admin.icon name="information" class="size-5" />
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
        @if ($tile->hint)
            <span>{{ $tile->hint }}</span>
        @endif
        @if ($tile->change)
            <span @class(['inline-flex items-center gap-1 font-medium', 'text-slate-600' => $tile->changeDirection === 'flat', 'text-slate-800' => $tile->changeDirection !== 'flat']) title="{{ __('reporting.dashboard.previous', ['days' => '']) }}">
                <span aria-hidden="true">{{ $tile->changeDirection === 'up' ? '▲' : ($tile->changeDirection === 'down' ? '▼' : '■') }}</span>
                <span dir="ltr">{{ $tile->change }}</span>
            </span>
        @endif
        <span @class(['badge ms-auto', 'badge-success' => $live, 'badge-neutral' => ! $live])>{{ $tile->freshness->label() }}</span>
    </div>

    <div id="{{ $id }}-info" x-show="info" x-cloak x-transition.opacity class="absolute inset-x-3 top-14 z-20 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-700 shadow-overlay" role="group" aria-label="{{ __('reporting.dashboard.definition') }}: {{ $key->label() }}">
        <dl class="space-y-2">
            <div><dt class="font-semibold text-slate-900">{{ __('reporting.dashboard.numerator') }}</dt><dd>{{ $tile->definition['numerator'] }}</dd></div>
            <div><dt class="font-semibold text-slate-900">{{ __('reporting.dashboard.denominator') }}</dt><dd>{{ $tile->definition['denominator'] }}</dd></div>
            <div><dt class="font-semibold text-slate-900">{{ __('reporting.dashboard.timezone') }}</dt><dd>{{ $tile->definition['timezone'] }}</dd></div>
            <div>
                <dt class="font-semibold text-slate-900">{{ __('reporting.dashboard.freshness') }}</dt>
                <dd>{{ $live ? __('reporting.freshness_note.live') : ($tile->asOf ? __('reporting.freshness_note.as_of', ['time' => $tile->asOf->copy()->setTimezone(config('admin.display_timezone', 'UTC'))->locale(app()->getLocale())->translatedFormat('Y-m-d H:i')]) : __('reporting.freshness_note.never')) }}</dd>
            </div>
        </dl>
    </div>
</div>
