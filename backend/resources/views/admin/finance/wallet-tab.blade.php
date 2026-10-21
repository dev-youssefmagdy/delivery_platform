{{-- Wallet tab of a customer or provider page: balances, the provider's cash debt against its limit, and the statement. --}}
@php
    /** @var \App\DTOs\Finance\WalletOverview $overview */
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $currencies = app(\App\Support\Money\CurrencyRegistry::class);
    $format = static fn (int $minor, string $currency): string => $money->format(\App\Support\Money\Money::of($minor, $currencies->get($currency)));
@endphp
@if (! empty($intro))
    <p class="mb-4 text-sm text-slate-500">{{ $intro }}</p>
@endif
@if ($overview->accounts === [])
    <x-admin.empty-state icon="banknotes" :title="__('admin.ledger.wallet.none')" compact />
@else
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($overview->accounts as $account)
            <x-admin.stat-tile
                :label="__('admin.ledger.wallet.balance', ['currency' => $account->currency])"
                :value="$format($account->balance_minor, $account->currency)"
                :hint="$account->balance_minor < 0 ? __('admin.ledger.wallet.owed_hint') : null"
                icon="banknotes"
                :tone="$account->balance_minor < 0 ? 'danger' : 'success'"
            />
        @endforeach
        @if ($overview->cashDebtMinor !== null)
            @php($currency = $overview->accounts[0]->currency)
            <x-admin.stat-tile
                :label="__('admin.ledger.wallet.cash_debt')"
                :value="$format($overview->cashDebtMinor, $currency)"
                :hint="__('admin.ledger.wallet.cash_limit', ['limit' => $format((int) $overview->cashDebtLimitMinor, $currency)])"
                icon="exclamation"
                :tone="$overview->overLimit() ? 'danger' : 'neutral'"
            />
        @endif
    </div>
    @if ($overview->overLimit())
        <x-admin.alert type="danger" class="mb-6">{{ __('admin.ledger.wallet.over_limit') }}</x-admin.alert>
    @endif
    @can('viewAny', \App\Models\LedgerAccount::class)
        <p class="mb-4 text-sm">
            @foreach ($overview->accounts as $account)
                <a href="{{ route('admin.ledger-accounts.show', $account) }}" class="me-4 text-brand-700 hover:underline">{{ __('admin.ledger.wallet.open_account', ['currency' => $account->currency]) }}</a>
            @endforeach
        </p>
    @endcan
@endif
<x-admin.datatable :table="$dataTable" />
