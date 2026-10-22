@php
    /** @var \App\DTOs\Booking\CancellationDecision $decision */
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get($decision->currency);
    $format = static fn (int $minor): string => $money->format(\App\Support\Money\Money::of($minor, $currency));
@endphp
<x-admin.detail-list>
    <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.result_fee')"><span class="text-base font-semibold">{{ $format($decision->feeMinor) }}</span></x-admin.detail-list.item>
    <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.payer')" :value="$decision->payer()->label()" />
    <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.basis')" :value="__('admin.cancellation_policies.preview.bases.'.$decision->basis)" />
    <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.stage')" :value="$decision->stage->label()" />
    @if ($decision->recipient)
        <x-admin.detail-list.item :label="__('admin.cancellation_policies.rules.recipient')" :value="$decision->recipient->label().($decision->providerShareBp !== null ? ' ('.($decision->providerShareBp / 100).'% '.__('admin.cancellation_policies.preview.to_provider').')' : '')" />
    @endif
    @if ($decision->penaltyMinor() > 0)
        <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.penalty')" :value="$format($decision->penaltyMinor())" />
    @endif
    <x-admin.detail-list.item :label="__('admin.cancellation_policies.preview.waiting')" :value="$waiting_minutes.' · '.$format($waiting_charge_minor)" />
</x-admin.detail-list>
