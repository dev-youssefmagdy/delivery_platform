@if ($receipt === null)
    <x-admin.empty-state icon="document" :title="__('admin.bookings.receipt.none')" compact />
@else
    @php($fare = $receipt['fare'])
    <p class="mb-4 text-sm text-slate-500">{{ __('admin.bookings.receipt.intro', ['number' => $receipt['number']]) }}@if ($receipt['backfilled']) {{ __('admin.bookings.receipt.backfilled') }}@endif</p>
    <x-admin.detail-list>
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.agreed')"><x-admin.money :minor="$fare['agreed_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        @foreach ($fare['adjustments'] as $adjustment)
            <x-admin.detail-list.item :label="\App\Enums\Booking\BookingAdjustmentType::from($adjustment['type'])->label()"><x-admin.money :minor="$adjustment['amount_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        @endforeach
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.subtotal')"><x-admin.money :minor="$fare['subtotal_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.discount')"><x-admin.money :minor="$fare['discount_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.fee')"><x-admin.money :minor="$fare['customer_fee_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.tax')"><x-admin.money :minor="$fare['tax_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        <x-admin.detail-list.item :label="__('admin.bookings.receipt.total')"><x-admin.money :minor="$fare['total_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        @if ($earnings)
            <x-admin.detail-list.item :label="__('admin.bookings.receipt.commission')"><x-admin.money :minor="$earnings['commission_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
            <x-admin.detail-list.item :label="__('admin.bookings.receipt.net')"><x-admin.money :minor="$earnings['net_minor']" :currency="$receipt['currency']" /></x-admin.detail-list.item>
        @endif
        @if ($canViewPii && ($receipt['booking']['pickup'] ?? '') !== '')
            <x-admin.detail-list.item :label="__('admin.bookings.receipt.pickup')" :value="$receipt['booking']['pickup']" full />
        @endif
    </x-admin.detail-list>
@endif
