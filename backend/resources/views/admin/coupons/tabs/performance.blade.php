@php
    $presenter = app(\App\Support\Coupon\CouponPresenter::class);
    $money = static fn (int $minor): string => $presenter->money($minor, $coupon->currency);
    $budgetBurn = $coupon->budget_minor ? min(100, (int) round((($coupon->spent_minor + $coupon->reserved_minor) / $coupon->budget_minor) * 100)) : null;
    $usageBurn = $coupon->global_limit ? min(100, (int) round((($coupon->used_count + $coupon->reserved_count) / $coupon->global_limit) * 100)) : null;
    $average = $stats['redemptions'] - $stats['reversed'] > 0 ? intdiv($stats['discount_minor'], $stats['redemptions'] - $stats['reversed']) : 0;
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-admin.stat-tile :label="__('admin.coupons.performance.uses')" :value="$coupon->used_count" icon="tag" tone="success" :hint="__('admin.coupons.performance.reserved_now', ['count' => $coupon->reserved_count])" />
    <x-admin.stat-tile :label="__('admin.coupons.performance.spend')" :value="$money($coupon->spent_minor)" icon="banknotes" tone="primary" :hint="__('admin.coupons.performance.held_now', ['amount' => $money($coupon->reserved_minor)])" />
    <x-admin.stat-tile :label="__('admin.coupons.performance.customers')" :value="$stats['customers']" icon="users" tone="info" :hint="__('admin.coupons.performance.average', ['amount' => $money($average)])" />
    <x-admin.stat-tile :label="__('admin.coupons.performance.attempts')" :value="$attempts['total']" icon="shield" tone="warning" :hint="__('admin.coupons.performance.rejected', ['count' => $attempts['rejected']])" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="card p-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.coupons.performance.burn') }}</h3>
        @if ($budgetBurn === null && $usageBurn === null)
            <p class="text-sm text-slate-500">{{ __('admin.coupons.performance.no_caps') }}</p>
        @endif
        @if ($budgetBurn !== null)
            <p class="mb-1 flex justify-between text-sm"><span>{{ __('admin.coupons.performance.budget_burn') }}</span><span class="tabular-nums">{{ $money($coupon->spent_minor + $coupon->reserved_minor) }} / {{ $money($coupon->budget_minor) }} ({{ $budgetBurn }}%)</span></p>
            <div class="mb-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $budgetBurn }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-brand-600" style="width: {{ $budgetBurn }}%"></div></div>
        @endif
        @if ($usageBurn !== null)
            <p class="mb-1 flex justify-between text-sm"><span>{{ __('admin.coupons.performance.usage_burn') }}</span><span class="tabular-nums">{{ $coupon->used_count + $coupon->reserved_count }} / {{ $coupon->global_limit }} ({{ $usageBurn }}%)</span></p>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $usageBurn }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-brand-600" style="width: {{ $usageBurn }}%"></div></div>
        @endif
    </section>

    <section class="card p-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.coupons.performance.funding') }}</h3>
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach (['platform' => 'platform_minor', 'provider' => 'provider_minor', 'partner' => 'partner_minor'] as $funder => $key)
                <li class="flex justify-between py-2"><span>{{ __('enums.discount_funding.'.$funder) }}</span><span class="tabular-nums">{{ $money($stats[$key]) }}</span></li>
            @endforeach
            <li class="flex justify-between py-2 text-slate-500"><span>{{ __('admin.coupons.performance.reversed') }}</span><span class="tabular-nums">{{ $stats['reversed'] }}</span></li>
        </ul>
    </section>

    <section class="card p-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.coupons.performance.daily') }}</h3>
        @if ($daily === [])
            <p class="text-sm text-slate-500">{{ __('admin.coupons.performance.none_yet') }}</p>
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($daily as $day => $row)
                    <li class="flex justify-between py-2"><span class="tabular-nums" dir="ltr">{{ $day }}</span><span>{{ $row['count'] }} · {{ $money($row['discount_minor']) }}</span></li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card p-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.coupons.performance.rejections') }}</h3>
        @if ($rejections === [])
            <p class="text-sm text-slate-500">{{ __('admin.coupons.performance.none_yet') }}</p>
        @else
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($rejections as $code => $total)
                    <li class="flex justify-between py-2"><span>{{ \App\Enums\Coupon\CouponReason::tryFrom($code)?->label() ?? $code }}</span><span class="tabular-nums">{{ $total }}</span></li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
