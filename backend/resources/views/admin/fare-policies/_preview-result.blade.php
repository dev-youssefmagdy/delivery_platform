@php
    $currency = $guidance->recommended->currency;
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $format = static fn (\App\Support\Money\Money $value): string => $money->format($value);
@endphp
<div class="rounded-(--radius-control) border border-slate-200 bg-slate-50 p-4">
    <dl class="grid gap-4 sm:grid-cols-3">
        <div><dt class="text-xs text-slate-500">{{ __('admin.fare_policies.preview.low') }}</dt><dd class="text-lg font-semibold tabular-nums">{{ $format($guidance->low) }}</dd></div>
        <div><dt class="text-xs text-slate-500">{{ __('admin.fare_policies.preview.recommended') }}</dt><dd class="text-lg font-semibold tabular-nums text-brand-700">{{ $format($guidance->recommended) }}</dd></div>
        <div><dt class="text-xs text-slate-500">{{ __('admin.fare_policies.preview.high') }}</dt><dd class="text-lg font-semibold tabular-nums">{{ $format($guidance->high) }}</dd></div>
    </dl>
    <h4 class="mb-2 mt-5 text-sm font-semibold text-slate-800">{{ __('admin.fare_policies.preview.factors') }}</h4>
    <ul class="divide-y divide-slate-200 text-sm">
        @foreach ($guidance->factors as $line)
            @php($row = $line->toArray())
            <li class="flex items-center justify-between gap-3 py-2">
                <span>{{ $row['label'] }}@if ($row['quantity'] !== null) <span class="text-slate-500">({{ $row['quantity'] }})</span>@endif</span>
                <span class="tabular-nums">{{ $format($line->amount) }}</span>
            </li>
        @endforeach
    </ul>
</div>
