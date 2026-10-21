<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\BookingListFilterData;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every query on `bookings`. Writing `status` is not in here on purpose: only BookingTransitionService does it.
 *
 * @extends BaseRepositoryInterface<Booking>
 */
interface BookingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Inserts the booking in its initial `draft` status.
     *
     * @param array<string, mixed> $attributes everything but `status`
     */
    public function insertDraft(array $attributes): Booking;

    /**
     * Writes any column, `status` included (the model refuses a changed status unless the write runs inside the status gate,
     * which only BookingTransitionService opens).
     *
     * @param array<string, mixed> $attributes
     */
    public function forceUpdate(Booking $booking, array $attributes): Booking;

    public function codeExists(string $code): bool;

    public function findOfCustomer(string $uuid, int $customerId): ?Booking;

    public function findByUuidForUpdate(string $uuid): ?Booking;

    public function findByIdempotencyKey(int $customerId, string $key): ?Booking;

    /**
     * Customer history, newest first.
     *
     * @return Builder<Booking>
     */
    public function customerQuery(int $customerId, BookingListFilterData $filters): Builder;

    /**
     * Rows of the Bookings tab in the Customers admin module (same filters, includes the customer's anonymised rows).
     *
     * @return Builder<Booking>
     */
    public function datatableQueryForCustomer(User $customer): Builder;

    /**
     * On-demand bookings of the customer that are still in flight (one active on-demand booking rule).
     */
    public function inFlightOnDemandCount(int $customerId, ?int $exceptId = null): int;

    /**
     * @return list<int> ids of searching bookings whose window ended, oldest first
     */
    public function searchesExpiredBefore(CarbonInterface $now, int $limit): array;

    /**
     * @return list<int> ids of draft scheduled bookings whose search opens at or before `$opensBefore`
     */
    public function scheduledDueBefore(CarbonInterface $opensBefore, int $limit): array;

    public function inFlightCountForCategory(int $serviceCategoryId): int;

    public function inFlightCountForZone(int $serviceZoneId): int;

    public function inFlightCountForCustomer(int $customerId): int;

    /**
     * Bookings a provider currently serves (assigned statuses).
     */
    public function assignedCountForProvider(int $providerProfileId): int;

    /**
     * The provider's current job (most recent assigned booking), if any.
     */
    public function currentForProvider(int $providerProfileId): ?Booking;

    /**
     * Every booking of the customer with its stops, for the personal-data export.
     *
     * @return Collection<int, Booking>
     */
    public function exportForCustomer(int $customerId): Collection;

    /**
     * Removes personal data from every booking of the customer (addresses, free text, exact coordinates, route shape)
     * while keeping the rows, amounts, statuses and references the money records point at (ADR-75, ADR-93).
     *
     * @return int bookings scrubbed
     */
    public function scrubPersonalDataForCustomer(int $customerId): int;
}
