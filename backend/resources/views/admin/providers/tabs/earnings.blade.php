{{-- Earnings tab: gross figures of completed jobs only. The wallet tab has the ledger balance; net earnings and payouts arrive with P3.4. --}}
<p class="mb-4 text-sm text-slate-500">{{ __('admin.providers.earnings.intro') }}</p>
@if ($totals === [])
    <x-admin.empty-state icon="banknotes" :title="__('admin.providers.earnings.none')" compact />
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($totals as $row)
            @php($money = \App\Support\Money\Money::of($row['gross_minor'], app(\App\Support\Money\CurrencyRegistry::class)->get($row['currency'])))
            <x-admin.stat-tile :label="__('admin.providers.earnings.gross', ['currency' => $row['currency']])" :value="app(\App\Support\Money\MoneyFormatter::class)->format($money)" :hint="__('admin.providers.earnings.jobs', ['count' => $row['jobs']])" icon="banknotes" tone="success" />
        @endforeach
    </div>
@endif
<x-admin.available-after class="mt-6" :title="__('admin.providers.tabs.earnings')" milestone="P3.4" :module="__('admin.providers.modules.earnings')" />
