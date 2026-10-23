@php
    use App\Enums\Reporting\DataFreshness;
    $aggregated = collect($board->tiles)->filter(fn ($tile) => $tile->freshness === DataFreshness::Aggregated);
    $sections = [
        'demand' => collect($board->tiles)->filter(fn ($tile) => in_array($tile->key->value, ['requests', 'request_to_offer', 'offer_to_selection', 'completion_rate', 'cancellation_rate', 'no_show_rate', 'avg_offer_response', 'waiting_requests', 'active_drivers'], true)),
        'finance' => collect($board->tiles)->filter(fn ($tile) => in_array($tile->key->value, ['estimated_gmv', 'realized_gmv', 'commission_revenue', 'payment_success_rate', 'payments_failed', 'pending_payouts'], true)),
        'queues' => collect($board->tiles)->filter(fn ($tile) => in_array($tile->key->value, ['open_incidents', 'open_tickets', 'pending_verifications', 'open_review_reports', 'open_appeals', 'active_risk_signals'], true)),
    ];
@endphp
<div class="mb-4 flex flex-wrap items-center gap-2">
    @if ($aggregated->isNotEmpty())
        <x-admin.freshness :at="$board->lastAggregation()" />
    @endif
    <x-admin.freshness :live="true" />
    <span class="text-xs text-slate-600">{{ __('reporting.dashboard.live_note') }}</span>
</div>

@forelse ($sections as $key => $tiles)
    @continue($tiles->isEmpty())
    <section class="mb-8" aria-labelledby="dash-{{ $key }}">
        <h2 id="dash-{{ $key }}" class="mb-3 text-sm font-semibold tracking-wide text-slate-600 uppercase">{{ __('reporting.dashboard.sections.'.$key) }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($tiles as $tile)
                <x-admin.kpi-tile :tile="$tile" />
            @endforeach
        </div>
    </section>
@empty
    <x-admin.empty-state icon="inbox" :title="__('reporting.dashboard.no_widgets')" />
@endforelse

@if (count($board->charts) > 0)
    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($board->charts as $panel)
            <x-admin.card>
                @if ($panel->chart->type === 'donut')
                    <x-admin.chart.donut :chart="$panel->chart" :title="$panel->title" :id="'chart-'.$panel->id" :href="$panel->href" :href-label="__('reporting.dashboard.open_listing')" />
                @elseif ($panel->chart->type === 'bar')
                    <x-admin.chart.bar :chart="$panel->chart" :title="$panel->title" :id="'chart-'.$panel->id" :href="$panel->href" :href-label="__('reporting.dashboard.open_report')" />
                @else
                    <x-admin.chart.line :chart="$panel->chart" :title="$panel->title" :id="'chart-'.$panel->id" :href="$panel->href" :href-label="__('reporting.dashboard.open_report')" />
                @endif
            </x-admin.card>
        @endforeach
    </div>
@endif
