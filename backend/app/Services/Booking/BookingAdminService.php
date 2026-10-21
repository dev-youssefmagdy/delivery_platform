<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingAdminDetails;
use App\Enums\Booking\BookingOverrideAction;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Read side of the admin booking show page (SRS §16.6). Phones are always masked; exact addresses and coordinates are
 * for admins with `bookings.view_pii`, and opening a booking that way is written to the audit log.
 */
final class BookingAdminService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
        private readonly BookingOverrideService $overrides,
        private readonly AuditLogger $audit,
    ) {}

    public function find(string $uuid): Booking
    {
        return $this->bookings->findForAdmin($uuid) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
    }

    public function details(Booking $booking, Admin $admin, bool $canViewPii): BookingAdminDetails
    {
        $booking->loadMissing(['customer', 'provider.user', 'serviceCategory', 'city', 'serviceZone', 'stops']);

        if ($canViewPii) {
            $this->audit->record(AuditAction::Viewed, $booking, $admin, meta: ['pii' => true]);
        }

        $customerPhone = $booking->customer->phone_e164;
        $providerPhone = $booking->provider?->user->phone_e164;

        return new BookingAdminDetails(
            booking: $booking,
            canViewPii: $canViewPii,
            availableOverrides: array_map(static fn (BookingOverrideAction $action): string => $action->value, $this->overrides->availableFor($booking, $admin)),
            forceTargets: array_map(static fn ($status): string => $status->value, $this->overrides->forceTargets($booking)),
            customerPhoneMasked: $customerPhone === null ? null : PhoneNumber::mask($customerPhone),
            providerPhoneMasked: $providerPhone === null ? null : PhoneNumber::mask($providerPhone),
            pinRequired: (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false),
            offerCount: $this->offers->allForBookingWithVersions($booking->id)->count(),
        );
    }
}
