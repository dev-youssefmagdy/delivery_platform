@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i:s');
    $debt = $overview->cashDebtMinor ?? 0;
    $currency = $reconciliation->currency;
@endphp
<x-admin.layouts.app :title="__('admin.cash.show_title')">
    <x-admin.page-header
        :title="__('admin.cash.show_title').' '.\Illuminate\Support\Str::substr($reconciliation->uuid, 0, 8)"
        :subtitle="$reconciliation->uuid"
        :breadcrumbs="[['label' => __('admin.cash.title'), 'url' => route('admin.cash-reconciliations.index')], ['label' => \Illuminate\Support\Str::substr($reconciliation->uuid, 0, 8)]]"
    >
        @can('settle', $reconciliation)
            <x-admin.button variant="success" icon="check-circle" data-modal-open="cash-settle">{{ __('admin.cash.actions.settle') }}</x-admin.button>
        @endcan
        @can('cancel', $reconciliation)
            <x-admin.confirm-action :url="route('admin.cash-reconciliations.cancel', $reconciliation)" :title="__('admin.cash.confirm.cancel_title')" :message="__('admin.cash.confirm.cancel_message')" :confirm-label="__('admin.cash.actions.cancel')" reason-required variant="warning" icon="x-mark">{{ __('admin.cash.actions.cancel') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card class="mb-6">
        <x-admin.detail-list>
            <x-admin.detail-list.item :label="__('admin.cash.fields.status')"><x-admin.badge :value="$reconciliation->status" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.provider')" :value="$reconciliation->provider->user->name" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.period')" :value="$reconciliation->period_from->toDateString().' → '.$reconciliation->period_to->toDateString()" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.jobs')" :value="$reconciliation->cash_jobs" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.cash_collected')"><x-admin.money :minor="$reconciliation->cash_collected_minor" :currency="$currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.commission_due')"><x-admin.money :minor="$reconciliation->commission_due_minor" :currency="$currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.settled')"><x-admin.money :minor="$reconciliation->settled_minor" :currency="$currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.wallet_debt')"><x-admin.money :minor="$debt" :currency="$currency" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.method')" :value="$reconciliation->method?->label()" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.reference')"><span class="ltr-only">{{ $reconciliation->reference ?? '—' }}</span></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.cash.fields.created_by')" :value="$reconciliation->createdBy?->name" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.settled_by')" :value="$reconciliation->settledBy?->name" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.settled_at')" :value="$when($reconciliation->settled_at)" />
            <x-admin.detail-list.item :label="__('admin.cash.fields.notes')" :value="$reconciliation->notes" full />
        </x-admin.detail-list>
    </x-admin.card>

    <x-admin.alert type="info" class="mb-6">{{ __('admin.cash.notice') }}</x-admin.alert>

    <x-admin.card :padding="false" class="p-5">
        <h3 class="mb-4 text-sm font-semibold text-slate-800">{{ __('admin.common.activity') }}</h3>
        @include('admin.partials.activity', ['logs' => $logs])
    </x-admin.card>

    @can('settle', $reconciliation)
        <x-admin.action-modal id="cash-settle" :title="__('admin.cash.settle_title')" :message="__('admin.cash.settle_message', ['debt' => app(\App\Support\Money\MoneyFormatter::class)->format(\App\Support\Money\Money::of($debt, app(\App\Support\Money\CurrencyRegistry::class)->get($currency)))])" :action="route('admin.cash-reconciliations.settle', $reconciliation)" :submit="__('admin.cash.actions.settle')" variant="success" icon="check-circle">
            <x-admin.form.money name="amount_minor" :label="__('admin.cash.fields.settle_amount')" :value="$debt" :currency="$currency" required />
            <x-admin.form.enum-select name="method" :enum="\App\Enums\Finance\CashSettlementMethod::class" :label="__('admin.cash.fields.method')" required />
            <x-admin.form.input name="reference" :label="__('admin.cash.fields.reference')" required maxlength="120" dir="ltr" />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>
