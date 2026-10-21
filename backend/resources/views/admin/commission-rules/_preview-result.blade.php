@php
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $format = static fn (\App\Support\Money\Money $value): string => $money->format($value);
    $rows = [
        'agreed_fare' => $breakdown->agreedFare,
        'customer_fee' => $breakdown->customerFee,
        'tax' => $breakdown->tax,
        'tip' => $breakdown->tip,
        'customer_payable' => $breakdown->customerPayable,
        'provider_gross' => $breakdown->providerGross,
        'commission' => $breakdown->commission,
        'tip_commission' => $breakdown->tipCommission,
        'provider_net' => $breakdown->providerNet,
        'provider_earning' => $breakdown->providerEarning,
        'platform_revenue' => $breakdown->platformRevenue,
    ];
@endphp
<div class="rounded-(--radius-control) border border-slate-200 bg-slate-50 p-4">
    <p class="mb-3 text-sm text-slate-600">{{ __('admin.commission_rules.preview.applied', ['terms' => app(\App\Support\Finance\CommissionTermsPresenter::class)->describe($breakdown->terms, $breakdown->currency->code)]) }}</p>
    <ul class="divide-y divide-slate-200 text-sm">
        @foreach ($rows as $key => $amount)
            <li class="flex items-center justify-between gap-3 py-2 @if (in_array($key, ['customer_payable', 'provider_earning', 'platform_revenue'], true)) font-semibold @endif">
                <span>{{ __('admin.price_breakdown.'.$key) }}</span>
                <span class="tabular-nums">{{ $format($amount) }}</span>
            </li>
        @endforeach
    </ul>
</div>
