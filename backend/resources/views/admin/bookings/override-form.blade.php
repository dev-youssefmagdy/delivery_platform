@php
    /** @var \App\Models\Booking $booking */
    use App\Enums\Booking\BookingOverrideAction as Action;
    $query = ['booking' => $booking, 'context' => $context];
    $config = match ($form) {
        Action::CancelByOperations->value => ['route' => 'admin.bookings.cancel', 'variant' => 'danger', 'icon' => 'x-mark', 'key' => 'cancel'],
        Action::ExtendSearch->value => ['route' => 'admin.bookings.extend-search', 'variant' => 'primary', 'icon' => 'clock', 'key' => 'extend_search'],
        Action::RedispatchNow->value => ['route' => 'admin.bookings.redispatch', 'variant' => 'primary', 'icon' => 'refresh', 'key' => 'redispatch'],
        Action::ForceTransition->value => ['route' => 'admin.bookings.force-transition', 'variant' => 'warning', 'icon' => 'bolt', 'key' => 'force_transition'],
        Action::LiftPinLock->value => ['route' => 'admin.bookings.lift-pin-lock', 'variant' => 'warning', 'icon' => 'lock-closed', 'key' => 'lift_pin_lock'],
        Action::LiftRecipientOtpLock->value => ['route' => 'admin.bookings.lift-recipient-otp-lock', 'variant' => 'warning', 'icon' => 'lock-closed', 'key' => 'lift_recipient_otp_lock'],
        Action::ResolveDeliveryAttempt->value => ['route' => 'admin.bookings.resolve-attempt', 'variant' => 'warning', 'icon' => 'bolt', 'key' => 'resolve_delivery_attempt'],
        default => ['route' => 'admin.bookings.contact', 'variant' => 'primary', 'icon' => 'inbox', 'key' => 'contact'],
    };
    $stage = \App\Enums\Booking\CancellationStage::of($booking->status);
@endphp
{{-- Modal fragment of one operator action (loaded into the global modal). Every action needs a reason. --}}
<h3 class="text-base font-semibold text-slate-900">{{ __('admin.bookings.forms.'.$config['key'].'.title', ['code' => $booking->code]) }}</h3>
<p class="mt-1 mb-4 text-sm text-slate-600">{{ __('admin.bookings.forms.'.$config['key'].'.message') }}</p>

<x-admin.form :action="route($config['route'], $query)" method="POST">
    <div class="space-y-4">
        @if ($form === Action::CancelByOperations->value)
            <x-admin.form.select
                name="fee"
                id="override-fee"
                :label="__('admin.bookings.fields.fee_decision')"
                :hint="__('admin.bookings.forms.cancel.fee_hint')"
                :options="array_filter([
                    \App\Enums\Booking\OperationsCancellationFee::Waive->value => \App\Enums\Booking\OperationsCancellationFee::Waive->label(),
                    \App\Enums\Booking\OperationsCancellationFee::ApplyPolicy->value => $stage ? \App\Enums\Booking\OperationsCancellationFee::ApplyPolicy->label() : null,
                ])"
                value="waive"
                required
            />
        @elseif ($form === Action::ExtendSearch->value)
            <x-admin.form.input
                name="extend_seconds"
                id="override-extend"
                type="number"
                :label="__('admin.bookings.fields.extend_seconds')"
                :hint="__('admin.bookings.forms.extend_search.seconds_hint', ['max' => config('booking.operations.extend_search_max_seconds')])"
                :value="config('booking.operations.extend_search_default_seconds')"
                min="30"
                :max="config('booking.operations.extend_search_max_seconds')"
                step="30"
                required
            />
        @elseif ($form === Action::ForceTransition->value)
            <x-admin.form.select
                name="target_status"
                id="override-target"
                :label="__('admin.bookings.fields.target_status')"
                :hint="__('admin.bookings.forms.force_transition.target_hint', ['status' => $booking->status->label()])"
                :options="collect($forceTargets)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                :placeholder="__('admin.form.select_placeholder')"
                required
            />
        @elseif ($form === Action::ResolveDeliveryAttempt->value)
            @php($retryLeft = $booking->delivery_attempts_count < \App\Services\Courier\DeliveryAttemptService::maxAttempts())
            <x-admin.form.select
                name="resolution"
                id="override-resolution"
                :label="__('admin.bookings.fields.resolution')"
                :hint="__('admin.bookings.forms.resolve_delivery_attempt.resolution_hint', ['count' => $booking->delivery_attempts_count, 'max' => \App\Services\Courier\DeliveryAttemptService::maxAttempts()])"
                :options="array_filter([
                    \App\Enums\Booking\DeliveryAttemptResolution::Retry->value => $retryLeft ? \App\Enums\Booking\DeliveryAttemptResolution::Retry->label() : null,
                    \App\Enums\Booking\DeliveryAttemptResolution::ReturnToSender->value => \App\Enums\Booking\DeliveryAttemptResolution::ReturnToSender->label(),
                    \App\Enums\Booking\DeliveryAttemptResolution::Operations->value => \App\Enums\Booking\DeliveryAttemptResolution::Operations->label(),
                ])"
                :placeholder="__('admin.form.select_placeholder')"
                required
            />
        @elseif ($form === 'contact')
            <x-admin.form.input name="subject" id="override-subject" :label="__('admin.customers.fields.message_subject')" maxlength="191" required />
            <x-admin.form.textarea name="body" id="override-body" :label="__('admin.customers.fields.message_body')" rows="4" maxlength="2000" required />
        @endif

        <x-admin.form.textarea
            name="reason"
            id="override-reason"
            :label="__('admin.common.reason')"
            :hint="__('admin.bookings.forms.reason_hint')"
            rows="3"
            required
            :minlength="config('admin.reason.min')"
            :maxlength="config('admin.reason.max')"
        />
    </div>
    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
        <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: 'admin-global-modal' })">{{ __('admin.common.cancel') }}</x-admin.button>
        <x-admin.button type="submit" :variant="$config['variant']" :icon="$config['icon']">{{ __('admin.bookings.forms.'.$config['key'].'.submit') }}</x-admin.button>
    </div>
</x-admin.form>
