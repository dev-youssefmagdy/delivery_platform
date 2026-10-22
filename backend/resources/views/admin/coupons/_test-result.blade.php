@php
    $result = $quote->result;
    $presenter = app(\App\Support\Coupon\CouponPresenter::class);
    $currency = $result->discount->currency->code;
@endphp
<div class="rounded-(--radius-control) border p-4 {{ $result->isEligible() ? 'border-success-200 bg-success-50' : 'border-danger-200 bg-danger-50' }}" role="status">
    <p class="text-sm font-semibold {{ $result->isEligible() ? 'text-success-700' : 'text-danger-700' }}">
        {{ $result->isEligible() ? __('admin.coupons.test.eligible') : __('admin.coupons.test.not_eligible') }}
        <span class="ltr-only ms-2 font-mono text-xs font-normal">{{ $result->reason->value }}</span>
    </p>
    <p class="mt-1 text-sm text-slate-700">{{ $result->reason->label() }}</p>
    <p class="mt-1 text-xs text-slate-500">{{ __('admin.coupons.test.customer_is', ['name' => $customer->name ?? __('admin.customers.unnamed')]) }}</p>
    @if ($result->isEligible())
        <ul class="mt-3 divide-y divide-slate-200 text-sm">
            <li class="flex justify-between py-2 font-semibold"><span>{{ __('admin.coupons.test.discount') }}</span><span class="tabular-nums">{{ $presenter->money($result->discount->amount, $currency) }}</span></li>
            <li class="flex justify-between py-2"><span>{{ __('enums.discount_funding.platform') }}</span><span class="tabular-nums">{{ $presenter->money($quote->platformMinor, $currency) }}</span></li>
            <li class="flex justify-between py-2"><span>{{ __('enums.discount_funding.provider') }}</span><span class="tabular-nums">{{ $presenter->money($quote->providerMinor, $currency) }}</span></li>
            <li class="flex justify-between py-2"><span>{{ __('enums.discount_funding.partner') }}</span><span class="tabular-nums">{{ $presenter->money($quote->partnerMinor, $currency) }}</span></li>
        </ul>
    @endif
</div>
