<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingListFilterData;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * A customer's own bookings. Every read is scoped by the owner id, so another customer's uuid answers 404 exactly like
 * a missing one (no existence oracle, Plan I10 / IDOR).
 */
final class BookingReadService
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function list(User $customer, BookingListFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return $this->bookings->customerQuery($customer->id, $filters)->paginate($perPage);
    }

    public function show(User $customer, string $uuid): Booking
    {
        $booking = $this->bookings->findOfCustomer($uuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $booking->load(['stops', 'statusEvents']);
    }
}
