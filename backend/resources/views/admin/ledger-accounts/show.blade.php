@php
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $balance = $account->balance;
@endphp
<x-admin.layouts.app :title="__('admin.ledger.accounts.show_title')">
    <x-admin.page-header
        :title="$account->type->label()"
        :subtitle="$account->uuid"
        :breadcrumbs="[['label' => __('admin.ledger.accounts.title'), 'url' => route('admin.ledger-accounts.index')], ['label' => '#'.$account->id]]"
    />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-admin.stat-tile :label="__('admin.ledger.accounts.fields.balance')" :value="$balance ? $money->format($balance) : '—'" icon="banknotes" :tone="$account->balance_minor < 0 ? 'danger' : 'success'" :hint="__('admin.ledger.accounts.balance_hint')" />
    </div>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'statement' => ['label' => __('admin.ledger.accounts.tabs.statement'), 'icon' => 'clipboard'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.type')" :value="$account->type->label()" />
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.currency')" :value="$account->currency" />
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.normal_side')" :value="$account->type->normalSide()->label()" />
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.policy')" :value="$account->allow_negative ? ($account->credit_limit_minor === null ? __('admin.ledger.accounts.policy.negative_allowed') : __('admin.ledger.accounts.policy.negative_limited')) : __('admin.ledger.accounts.policy.never_negative')" />
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.owner')">
                        @if ($account->type === \App\Enums\Finance\LedgerAccountType::CustomerWallet && $account->owner instanceof \App\Models\User)
                            @can('view', $account->owner)
                                <a href="{{ route('admin.customers.show', $account->owner) }}" class="text-brand-700 hover:underline">{{ __('admin.ledger.accounts.owner_customer', ['ref' => \Illuminate\Support\Str::substr($account->owner->uuid, 0, 8)]) }}</a>
                            @else
                                {{ __('admin.ledger.accounts.owner_customer', ['ref' => \Illuminate\Support\Str::substr($account->owner->uuid, 0, 8)]) }}
                            @endcan
                        @elseif ($account->type === \App\Enums\Finance\LedgerAccountType::ProviderWallet && $account->owner instanceof \App\Models\ProviderProfile)
                            @can('view', $account->owner)
                                <a href="{{ route('admin.providers.show', $account->owner) }}" class="text-brand-700 hover:underline">{{ __('admin.ledger.accounts.owner_provider', ['ref' => \Illuminate\Support\Str::substr($account->owner->uuid, 0, 8)]) }}</a>
                            @else
                                {{ __('admin.ledger.accounts.owner_provider', ['ref' => \Illuminate\Support\Str::substr($account->owner->uuid, 0, 8)]) }}
                            @endcan
                        @else
                            {{ __('admin.ledger.accounts.owner_platform') }}
                        @endif
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.created')" :value="$when($account->created_at)" />
                    <x-admin.detail-list.item :label="__('admin.ledger.accounts.fields.updated')" :value="$when($account->updated_at)" />
                </x-admin.detail-list>
                <x-admin.alert type="info" class="mt-6">{{ __('admin.ledger.accounts.immutable_notice') }}</x-admin.alert>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="statement" :url="route('admin.ledger-accounts.statement', $account)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
