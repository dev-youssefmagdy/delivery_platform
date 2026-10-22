@php
    /** @var array<string, mixed> $receipt */
    $booking = $receipt['booking'];
    $fare = $receipt['fare'];
    $serviceNames = (array) ($booking['service'] ?? []);
    $service = $serviceNames[$locale] ?? $serviceNames['ar'] ?? '';
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?string $iso): ?string => $iso === null ? null : \Illuminate\Support\Carbon::parse($iso)->setTimezone($tz)->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('website.receipt.title') }} {{ $receipt['number'] }}</title>
    <style>
        :root { color-scheme: light; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --bg: #f8fafc; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Tahoma, "Noto Naskh Arabic", sans-serif; line-height: 1.5; }
        main { max-width: 42rem; margin: 0 auto; padding: 1rem; }
        article { background: #fff; border: 1px solid var(--line); border-radius: .75rem; padding: 1.25rem; }
        h1 { margin: 0; font-size: 1.25rem; }
        h2 { margin: 1.5rem 0 .5rem; font-size: .8rem; letter-spacing: .04em; color: var(--muted); text-transform: uppercase; }
        .meta { color: var(--muted); font-size: .875rem; margin-block-start: .25rem; }
        dl { margin: 0; }
        .row { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; border-block-end: 1px solid var(--line); font-size: .9375rem; }
        .row:last-child { border-block-end: 0; }
        .row dt { color: var(--muted); }
        .row dd { margin: 0; font-variant-numeric: tabular-nums; text-align: end; }
        .total { font-weight: 700; font-size: 1.0625rem; }
        .note { margin-block-start: 1rem; font-size: .8125rem; color: var(--muted); }
        .actions { margin-block-start: 1rem; text-align: center; }
        button { font: inherit; padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--line); background: #fff; cursor: pointer; }
        @page { size: A4; margin: 14mm; }
        @media print { body { background: #fff; } article { border: 0; padding: 0; } .actions { display: none; } }
    </style>
</head>
<body>
<main>
    <article>
        <h1>{{ $party === 'provider' ? __('website.receipt.job_title') : __('website.receipt.title') }}</h1>
        <p class="meta">{{ $receipt['number'] }} · {{ $when($receipt['issued_at']) }}</p>

        <h2>{{ __('website.receipt.trip') }}</h2>
        <dl>
            <div class="row"><dt>{{ __('website.receipt.booking') }}</dt><dd dir="ltr">{{ $booking['code'] }}</dd></div>
            @if ($service !== '')
                <div class="row"><dt>{{ __('website.receipt.service') }}</dt><dd>{{ $service }}</dd></div>
            @endif
            @if ($booking['pickup'] !== '')
                <div class="row"><dt>{{ __('website.receipt.pickup') }}</dt><dd>{{ $booking['pickup'] }}</dd></div>
            @endif
            @if ($booking['dropoff'] !== '')
                <div class="row"><dt>{{ __('website.receipt.dropoff') }}</dt><dd>{{ $booking['dropoff'] }}</dd></div>
            @endif
            @if ($booking['completed_at'])
                <div class="row"><dt>{{ __('website.receipt.completed_at') }}</dt><dd>{{ $when($booking['completed_at']) }}</dd></div>
            @endif
            @if ($party === 'customer' && ($receipt['parties']['provider']['name'] ?? null))
                <div class="row"><dt>{{ __('website.receipt.driver') }}</dt><dd>{{ $receipt['parties']['provider']['name'] }}</dd></div>
            @endif
            @if ($party === 'provider' && ($receipt['parties']['customer']['name'] ?? null))
                <div class="row"><dt>{{ __('website.receipt.customer') }}</dt><dd>{{ $receipt['parties']['customer']['name'] }}</dd></div>
            @endif
            <div class="row"><dt>{{ __('website.receipt.payment') }}</dt><dd>{{ __('enums.payment_method_type.'.$receipt['payment']['method']) }}</dd></div>
        </dl>

        <h2>{{ __('website.receipt.fare') }}</h2>
        <dl>
            <div class="row"><dt>{{ __('website.receipt.agreed_fare') }}</dt><dd>{{ $money($fare['agreed_minor']) }}</dd></div>
            @foreach ($fare['adjustments'] as $adjustment)
                <div class="row"><dt>{{ __('enums.booking_adjustment_type.'.$adjustment['type']) }}@if ($adjustment['reason']) <span class="note">· {{ $adjustment['reason'] }}</span>@endif</dt><dd dir="ltr">{{ $adjustment['amount_minor'] >= 0 ? '+' : '' }}{{ $money($adjustment['amount_minor']) }}</dd></div>
            @endforeach
            <div class="row"><dt>{{ __('website.receipt.subtotal') }}</dt><dd>{{ $money($fare['subtotal_minor']) }}</dd></div>
            @if ($party === 'customer')
                @if ($fare['discount_minor'] > 0)
                    <div class="row"><dt>{{ __('website.receipt.discount') }}</dt><dd dir="ltr">-{{ $money($fare['discount_minor']) }}</dd></div>
                @endif
                @if ($fare['customer_fee_minor'] > 0)
                    <div class="row"><dt>{{ __('website.receipt.service_fee') }}</dt><dd>{{ $money($fare['customer_fee_minor']) }}</dd></div>
                @endif
                @if ($fare['tax_minor'] > 0)
                    <div class="row"><dt>{{ __('website.receipt.tax') }}</dt><dd>{{ $money($fare['tax_minor']) }}</dd></div>
                @endif
                <div class="row total"><dt>{{ __('website.receipt.total') }}</dt><dd>{{ $money($fare['total_minor']) }}</dd></div>
            @endif
        </dl>

        @if ($party === 'provider')
            <h2>{{ __('website.receipt.earnings') }}</h2>
            <dl>
                <div class="row"><dt>{{ __('website.receipt.gross') }}</dt><dd>{{ $money($receipt['earnings']['gross_minor']) }}</dd></div>
                <div class="row"><dt>{{ __('website.receipt.commission') }}</dt><dd dir="ltr">-{{ $money($receipt['earnings']['commission_minor']) }}</dd></div>
                @if ($receipt['earnings']['discount_provider_minor'] > 0)
                    <div class="row"><dt>{{ __('website.receipt.provider_discount') }}</dt><dd dir="ltr">-{{ $money($receipt['earnings']['discount_provider_minor']) }}</dd></div>
                @endif
                <div class="row total"><dt>{{ __('website.receipt.net') }}</dt><dd>{{ $money($receipt['earnings']['net_minor']) }}</dd></div>
            </dl>
        @endif

        <p class="note">{{ __('website.receipt.footer') }}</p>
    </article>
    <p class="actions"><button type="button" onclick="window.print()">{{ __('website.receipt.print') }}</button></p>
</main>
</body>
</html>
