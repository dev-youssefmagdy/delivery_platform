@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $currencies = app(\App\Support\Money\CurrencyRegistry::class);
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $format = static fn (int $minor, string $currency): string => $money->format(\App\Support\Money\Money::of($minor, $currencies->get($currency)));
    $findings = $run->findings ?? [];
@endphp
<x-admin.layouts.app :title="__('admin.ledger.reconciliation.show_title')">
    <x-admin.page-header
        :title="__('admin.ledger.reconciliation.show_title')"
        :subtitle="$run->uuid"
        :breadcrumbs="[['label' => __('admin.ledger.reconciliation.title'), 'url' => route('admin.ledger-reconciliation.index')], ['label' => '#'.$run->id]]"
    />

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.ledger.reconciliation.fields.status')"><x-admin.badge :value="$run->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.ledger.reconciliation.fields.ran_at')" :value="$when($run->ran_at)" />
            <x-admin.detail-list.item :label="__('admin.ledger.reconciliation.fields.accounts')" :value="$run->accounts_checked" />
            <x-admin.detail-list.item :label="__('admin.ledger.reconciliation.fields.entries')" :value="$run->entries_checked" />
            <x-admin.detail-list.item :label="__('admin.ledger.reconciliation.fields.triggered_by')" :value="$run->triggered_by_type === 'admin' ? __('admin.ledger.reconciliation.by_admin') : __('admin.ledger.reconciliation.by_system')" />
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.ledger.reconciliation.findings_title') }}</h3>
        @if ($findings === [])
            <x-admin.empty-state icon="check-circle" :title="__('admin.ledger.reconciliation.no_findings')" compact />
        @else
            <x-admin.alert type="warning" class="mb-4">{{ __('admin.ledger.reconciliation.no_autofix') }}</x-admin.alert>
            <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                @foreach ($findings as $finding)
                    <li class="px-4 py-3">
                        <p class="font-medium text-danger-700">{{ __('admin.ledger.reconciliation.kinds.'.$finding['kind']) }}</p>
                        @if ($finding['kind'] === 'balance_mismatch')
                            <p class="mt-1 text-slate-700">
                                @can('viewAny', \App\Models\LedgerAccount::class)
                                    <a href="{{ route('admin.ledger-accounts.show', $finding['account_uuid']) }}" class="ltr-only text-brand-700 hover:underline">{{ \Illuminate\Support\Str::substr($finding['account_uuid'], 0, 8) }}</a>
                                @else
                                    <span class="ltr-only">{{ \Illuminate\Support\Str::substr($finding['account_uuid'], 0, 8) }}</span>
                                @endcan
                                · {{ \App\Enums\Finance\LedgerAccountType::tryFrom($finding['account_type'])?->label() }}
                                · {{ __('admin.ledger.reconciliation.expected') }} <span class="tabular-nums" dir="ltr">{{ $format((int) $finding['expected_minor'], $finding['currency']) }}</span>
                                · {{ __('admin.ledger.reconciliation.cached') }} <span class="tabular-nums" dir="ltr">{{ $format((int) $finding['cached_minor'], $finding['currency']) }}</span>
                            </p>
                        @elseif ($finding['kind'] === 'unbalanced_entry')
                            <p class="mt-1 text-slate-700">
                                @can('viewAny', \App\Models\JournalEntry::class)
                                    <a href="{{ route('admin.journal-entries.show', $finding['entry_uuid']) }}" class="ltr-only text-brand-700 hover:underline">{{ \Illuminate\Support\Str::substr($finding['entry_uuid'], 0, 8) }}</a>
                                @else
                                    <span class="ltr-only">{{ \Illuminate\Support\Str::substr($finding['entry_uuid'], 0, 8) }}</span>
                                @endcan
                                · {{ $finding['currency'] }} · {{ __('admin.ledger.reconciliation.difference') }} <span class="tabular-nums" dir="ltr">{{ $format((int) $finding['difference_minor'], $finding['currency']) }}</span>
                            </p>
                        @else
                            <p class="mt-1 text-slate-700">
                                <span class="ltr-only">{{ \Illuminate\Support\Str::substr($finding['entry_uuid'], 0, 8) }}</span>
                                · {{ $finding['line_currency'] }} / {{ $finding['account_currency'] }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>
</x-admin.layouts.app>
