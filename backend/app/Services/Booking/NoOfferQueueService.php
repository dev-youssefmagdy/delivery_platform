<?php

namespace App\Services\Booking;

use App\DTOs\Customers\HelpMessageData;
use App\Enums\Admin\AdminPermission;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ForbiddenActionException;
use App\Models\Admin;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Customers\CustomerMessageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The no-offer queue's "contact the customer" action (SRS §16.6): an in-app help message about this booking (the message service notifies the customer). Retry,
 * extend and cancel are the override actions of `BookingOverrideService`; this class only adds the contact step.
 */
final class NoOfferQueueService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly CustomerMessageService $messages,
    ) {}

    /**
     * @throws BusinessRuleViolation `customer_anonymised`
     */
    public function contactCustomer(Admin $actor, string $bookingUuid, HelpMessageData $message): void
    {
        if (! $actor->can(AdminPermission::BookingsManage->value)) {
            throw ForbiddenActionException::make('override_forbidden');
        }

        $booking = $this->bookings->findForAdmin($bookingUuid) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        $this->messages->send($booking->customer, $message, $actor);
    }
}
