{{-- Earnings tab: figures from the stored price breakdowns of settled bookings (the same numbers the ledger was posted from). --}}
@php
    /** @var array<string, array<string, mixed>> $summaries */
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $currencies = app(\App\Support\Money\CurrencyRegistry::class);
    $month = $summaries['month'];
    $format = static fn (int $minor): string => $money->format(\App\Support\Money\Money::of($minor, $currencies->get($month['currency'])));
@endphp
<p class="mb-4 text-sm text-slate-500">{{ __('admin.providers.earnings.intro') }}</p>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.stat-tile :label="__('admin.providers.earnings.wallet_balance')" :value="$format($month['wallet']['balance_minor'])" :hint="__('admin.providers.earnings.wallet_hint')" icon="banknotes" :tone="$month['wallet']['balance_minor'] < 0 ? 'danger' : 'success'" />
    <x-admin.stat-tile :label="__('admin.providers.earnings.reserved')" :value="$format($month['wallet']['reserved_minor'])" :hint="__('admin.providers.earnings.reserved_hint')" icon="clock" tone="neutral" />
    <x-admin.stat-tile :label="__('admin.providers.earnings.available')" :value="$format($month['wallet']['available_minor'])" icon="check-circle" tone="success" />
    <x-admin.stat-tile :label="__('admin.providers.earnings.cash_debt')" :value="$format($month['wallet']['cash_debt_minor'])" :hint="__('admin.ledger.wallet.cash_limit', ['limit' => $format($month['wallet']['cash_debt_limit_minor'])])" icon="exclamation" :tone="$month['wallet']['cash_debt_minor'] > $month['wallet']['cash_debt_limit_minor'] ? 'danger' : 'neutral'" />
</div>

@foreach ($summaries as $period => $summary)
    @php($totals = $summary['totals'])
    <h3 class="mb-3 mt-6 text-sm font-semibold text-slate-800">{{ __('admin.providers.earnings.periods.'.$period) }}</h3>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-7">
        <x-admin.stat-tile :label="__('admin.providers.earnings.jobs_label')" :value="(string) $totals['jobs']" icon="clipboard" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.earning')" :value="$format($totals['earnings_minor'])" icon="banknotes" tone="success" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.gross')" :value="$format($totals['gross_minor'])" icon="banknotes" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.commission')" :value="$format($totals['commission_minor'])" icon="banknotes" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.provider_discount')" :value="$format($totals['discount_provider_minor'])" icon="banknotes" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.tip')" :value="$format($totals['tips_minor'])" icon="banknotes" tone="neutral" />
        <x-admin.stat-tile :label="__('admin.providers.earnings.columns.cash_collected')" :value="$format($totals['cash_collected_minor'])" icon="banknotes" tone="neutral" />
    </div>
@endforeach

<h3 class="mb-3 mt-8 text-sm font-semibold text-slate-800">{{ __('admin.providers.earnings.by_category') }}</h3>
@if ($month['by_category'] === [])
    <x-admin.empty-state icon="banknotes" :title="__('admin.providers.earnings.none')" compact />
@else
    <div class="overflow-x-auto rounded-(--radius-card) border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-start text-xs font-semibold text-slate-600">
                <tr>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.earnings.category') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.earnings.jobs_label') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.earnings.columns.gross') }}</th>
                    <th class="px-4 py-2 text-start">{{ __('admin.providers.earnings.columns.earning') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($month['by_category'] as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['name'][app()->getLocale()] ?? ($row['name']['ar'] ?? ($row['code'] ?? '—')) }}</td>
                        <td class="px-4 py-2 tabular-nums">{{ $row['jobs'] }}</td>
                        <td class="px-4 py-2 tabular-nums" dir="ltr">{{ $format($row['gross_minor']) }}</td>
                        <td class="px-4 py-2 tabular-nums" dir="ltr">{{ $format($row['earnings_minor']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@can('exportStatements', \App\Models\LedgerAccount::class)
    <form method="GET" action="{{ route('admin.providers.statement', $provider) }}" class="mt-8 flex flex-wrap items-end gap-3 rounded-(--radius-card) border border-slate-200 p-4">
        <div>
            <label for="statement-from" class="form-label">{{ __('admin.providers.earnings.statement_from') }}</label>
            <input type="date" id="statement-from" name="from" class="form-control" dir="ltr">
        </div>
        <div>
            <label for="statement-to" class="form-label">{{ __('admin.providers.earnings.statement_to') }}</label>
            <input type="date" id="statement-to" name="to" class="form-control" dir="ltr">
        </div>
        <x-admin.button type="submit" variant="secondary" icon="download">{{ __('admin.providers.earnings.export_statement') }}</x-admin.button>
        <p class="basis-full text-xs text-slate-500">{{ __('admin.providers.earnings.statement_hint') }}</p>
    </form>
@endcan

<h3 class="mb-3 mt-8 text-sm font-semibold text-slate-800">{{ __('admin.providers.earnings.transactions') }}</h3>
<x-admin.datatable :table="$dataTable" />
