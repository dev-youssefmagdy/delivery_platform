@php
    /** @var \App\DTOs\Booking\BookingAdminDetails $details */
    $booking = $details->booking;
    $tz = (string) config('admin.display_timezone');
    $format = static fn (?\Carbon\CarbonInterface $date): ?string => $date?->copy()->setTimezone($tz)->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
    $category = (array) ($booking->service_snapshot['category']['name'] ?? []);
    $serviceName = $category[app()->getLocale()] ?? $category['ar'] ?? $booking->serviceCategory->code;
    $form = static fn (string $name): string => route('admin.bookings.override-form', ['booking' => $booking, 'form' => $name]);
    $can = static fn (\App\Enums\Booking\BookingOverrideAction $action): bool => in_array($action->value, $details->availableOverrides, true);
    $pickup = $booking->pickup_point;
    $precision = $details->canViewPii ? 6 : 2;
    $pickupLat = round($pickup->lat, $precision);
    $pickupLng = round($pickup->lng, $precision);
@endphp
<x-admin.layouts.app :title="__('admin.bookings.show_title')">
    <x-admin.page-header
        :title="$booking->code"
        :subtitle="__('admin.bookings.show_subtitle', ['uuid' => $booking->uuid])"
        :breadcrumbs="[['label' => __('admin.bookings.title'), 'url' => route('admin.bookings.index')], ['label' => $booking->code]]"
    >
        <x-admin.badge :value="$booking->status" />
        @if ($can(\App\Enums\Booking\BookingOverrideAction::ExtendSearch))
            <x-admin.button variant="secondary" icon="clock" data-modal-open="admin-global-modal" :data-modal-url="$form('extend_search')">{{ __('admin.bookings.actions.extend_search') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::RedispatchNow))
            <x-admin.button variant="secondary" icon="refresh" data-modal-open="admin-global-modal" :data-modal-url="$form('redispatch_now')">{{ __('admin.bookings.actions.redispatch') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::LiftPinLock))
            <x-admin.button variant="warning" icon="lock-closed" data-modal-open="admin-global-modal" :data-modal-url="$form('lift_pin_lock')">{{ __('admin.bookings.actions.lift_pin_lock') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::LiftRecipientOtpLock))
            <x-admin.button variant="warning" icon="lock-closed" data-modal-open="admin-global-modal" :data-modal-url="$form('lift_recipient_otp_lock')">{{ __('admin.bookings.actions.lift_recipient_otp_lock') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::ResolveDeliveryAttempt))
            <x-admin.button variant="warning" icon="bolt" data-modal-open="admin-global-modal" :data-modal-url="$form('resolve_delivery_attempt')">{{ __('admin.bookings.actions.resolve_delivery_attempt') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::ForceTransition))
            <x-admin.button variant="warning" icon="bolt" data-modal-open="admin-global-modal" :data-modal-url="$form('force_transition')">{{ __('admin.bookings.actions.force_transition') }}</x-admin.button>
        @endif
        @if ($can(\App\Enums\Booking\BookingOverrideAction::CancelByOperations))
            <x-admin.button variant="danger" icon="x-mark" data-modal-open="admin-global-modal" :data-modal-url="$form('cancel_by_operations')">{{ __('admin.bookings.actions.cancel') }}</x-admin.button>
        @endif
    </x-admin.page-header>

    @if ($booking->pin_locked_at)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.bookings.notices.pin_locked')">{{ __('admin.bookings.notices.pin_locked_hint', ['attempts' => $booking->pin_attempts]) }}</x-admin.alert>
    @endif
    @if ($booking->support_flagged_at)
        <x-admin.alert type="warning" class="mb-6" :title="__('admin.bookings.notices.support_flagged')">{{ __('admin.bookings.notices.support_flagged_hint', ['date' => $format($booking->support_flagged_at)]) }}</x-admin.alert>
    @endif
    @if ($booking->status->isCancellation())
        <x-admin.alert type="danger" class="mb-6" :title="$booking->status->label()">
            {{ __('admin.bookings.notices.cancelled_at', ['date' => $format($booking->cancelled_at)]) }}
            @if ($booking->cancellation_reason)
                <span class="block">{{ __('admin.bookings.notices.reason', ['reason' => $booking->cancellation_reason]) }}</span>
            @endif
            @if ($booking->cancellation_fee_minor)
                <span class="block">{{ __('admin.bookings.notices.fee') }}: <x-admin.money :minor="$booking->cancellation_fee_minor" :currency="$booking->currency" />@if ($booking->cancellation_fee_posted_at) · {{ __('admin.bookings.notices.fee_posted') }}@endif</span>
            @endif
        </x-admin.alert>
    @endif

    @php
        $tabs = [
            'overview' => ['label' => __('admin.bookings.tabs.overview'), 'icon' => 'clipboard'],
            'timeline' => ['label' => __('admin.bookings.tabs.timeline'), 'icon' => 'clock'],
            'offers' => ['label' => __('admin.bookings.tabs.offers').' ('.$details->offerCount.')', 'icon' => 'tag'],
            'dispatch' => ['label' => __('admin.bookings.tabs.dispatch'), 'icon' => 'bolt'],
            'snapshots' => ['label' => __('admin.bookings.tabs.snapshots'), 'icon' => 'document'],
            'safety' => ['label' => __('admin.bookings.tabs.safety'), 'icon' => 'shield'],
            ...(app(\App\Services\Booking\BookingDeliveryAdminService::class)->applies($booking) ? ['delivery' => ['label' => __('admin.bookings.tabs.delivery'), 'icon' => 'cube']] : []),
            'ratings' => ['label' => __('admin.bookings.tabs.ratings'), 'icon' => 'star'],
            ...(\Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Payment::class) ? ['payment' => ['label' => __('admin.bookings.tabs.payment'), 'icon' => 'banknotes']] : []),
            'adjustments' => ['label' => __('admin.booking_adjustments.tab'), 'icon' => 'banknotes'],
            'receipt' => ['label' => __('admin.bookings.tabs.receipt'), 'icon' => 'document'],
            'audit' => ['label' => __('admin.bookings.tabs.audit'), 'icon' => 'shield'],
        ];
    @endphp

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="$tabs">
            <x-admin.tabs.panel id="overview">
                @include('admin.bookings._summary')
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="timeline" :url="route('admin.bookings.tabs.timeline', $booking)" />
            <x-admin.tabs.panel id="offers" :url="route('admin.bookings.tabs.offers', $booking)" />
            <x-admin.tabs.panel id="dispatch" :url="route('admin.bookings.tabs.dispatch', $booking)" />
            <x-admin.tabs.panel id="snapshots" :url="route('admin.bookings.tabs.snapshots', $booking)" />
            <x-admin.tabs.panel id="safety" :url="route('admin.bookings.tabs.safety', $booking)" />
            @if (app(\App\Services\Booking\BookingDeliveryAdminService::class)->applies($booking))
                <x-admin.tabs.panel id="delivery" :url="route('admin.bookings.tabs.delivery', $booking)" />
            @endif
            <x-admin.tabs.panel id="ratings" :url="route('admin.bookings.tabs.ratings', $booking)" />
            @can('viewAny', \App\Models\Payment::class)
                <x-admin.tabs.panel id="payment" :url="route('admin.bookings.tabs.payments', $booking)" />
            @endcan
            <x-admin.tabs.panel id="adjustments" :url="route('admin.bookings.tabs.adjustments', $booking)" />
            <x-admin.tabs.panel id="receipt" :url="route('admin.bookings.tabs.receipt', $booking)" />
            <x-admin.tabs.panel id="audit" :url="route('admin.bookings.tabs.audit', $booking)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
