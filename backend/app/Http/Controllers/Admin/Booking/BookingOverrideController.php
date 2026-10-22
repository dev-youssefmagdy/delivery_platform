<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\NoOfferQueueDataTable;
use App\Enums\Booking\BookingOverrideAction;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Booking\CancelBookingByOperationsRequest;
use App\Http\Requests\Admin\Booking\ContactBookingCustomerRequest;
use App\Http\Requests\Admin\Booking\ExtendBookingSearchRequest;
use App\Http\Requests\Admin\Booking\ForceBookingTransitionRequest;
use App\Http\Requests\Admin\Booking\LiftPinLockRequest;
use App\Http\Requests\Admin\Booking\LiftRecipientOtpLockRequest;
use App\Http\Requests\Admin\Booking\RedispatchBookingRequest;
use App\Http\Requests\Admin\Booking\ResolveDeliveryAttemptRequest;
use App\Models\Booking;
use App\Services\Booking\BookingOverrideService;
use App\Services\Booking\NoOfferQueueService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Operator overrides (SRS §8.3, §16.6): each write is a FormRequest (permission + mandatory reason) -> DTO -> one service
 * call. The same forms are served as modal fragments for the booking page and the no-offer queue (`context=queue` reloads
 * the queue table instead of the page).
 */
final class BookingOverrideController extends AdminController
{
    public function __construct(
        private readonly BookingOverrideService $overrides,
        private readonly NoOfferQueueService $queue,
    ) {}

    /**
     * The form fragment of one action (loaded into the global modal).
     */
    public function form(Request $request, Booking $booking, string $form): View
    {
        $ability = match ($form) {
            BookingOverrideAction::CancelByOperations->value => 'cancelByOperations',
            BookingOverrideAction::ExtendSearch->value, BookingOverrideAction::RedispatchNow->value, BookingOverrideAction::ResolveDeliveryAttempt->value, 'contact' => 'manage',
            BookingOverrideAction::ForceTransition->value => 'forceTransition',
            BookingOverrideAction::LiftPinLock->value => 'liftPinLock',
            BookingOverrideAction::LiftRecipientOtpLock->value => 'liftRecipientOtpLock',
            default => abort(404),
        };

        Gate::authorize($ability, $booking);

        return view('admin.bookings.override-form', [
            'booking' => $booking,
            'form' => $form,
            'context' => $request->query('context') === 'queue' ? 'queue' : 'show',
            'forceTargets' => $this->overrides->forceTargets($booking),
        ]);
    }

    public function cancel(CancelBookingByOperationsRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->cancelByOperations($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.cancelled'));
    }

    public function extendSearch(ExtendBookingSearchRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->extendSearch($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.search_extended'));
    }

    public function redispatch(RedispatchBookingRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->redispatchNow($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.redispatched'));
    }

    public function forceTransition(ForceBookingTransitionRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->forceTransition($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.status_forced'));
    }

    public function liftPinLock(LiftPinLockRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->liftPinLock($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.pin_lock_lifted'));
    }

    public function liftRecipientOtpLock(LiftRecipientOtpLockRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->liftRecipientOtpLock($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.recipient_otp_lock_lifted'));
    }

    public function resolveAttempt(ResolveDeliveryAttemptRequest $request, Booking $booking): AdminFormResponse
    {
        $this->overrides->resolveDeliveryAttempt($this->actor(), $booking->uuid, $request->toDto());

        return $this->done($request, __('admin.bookings.messages.attempt_resolved'));
    }

    public function contact(ContactBookingCustomerRequest $request, Booking $booking): AdminFormResponse
    {
        $this->queue->contactCustomer($this->actor(), $booking->uuid, $request->toMessage());

        return $this->done($request, __('admin.bookings.messages.customer_contacted'));
    }

    private function done(Request $request, string $message): AdminFormResponse
    {
        $response = $this->formResponse($message)->closeModal();

        return $request->query('context') === 'queue' ? $response->reloadTable(NoOfferQueueDataTable::TABLE_ID) : $response->reload();
    }
}
