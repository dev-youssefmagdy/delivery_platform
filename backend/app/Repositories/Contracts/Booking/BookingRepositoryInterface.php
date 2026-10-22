<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\BookingAdminFilterData;
use App\DTOs\Booking\BookingListFilterData;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * The owner's public id (the realtime channel name of the customer), or null for an unknown booking.
     */
    public function customerUuidOfBooking(string $bookingUuid): ?string;

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
     * Trips the customer has completed (a customer with none is "new" for coupon segments).
     */
    public function completedCountForCustomer(int $customerId): int;

    /**
     * @return list<int> ids of searching bookings whose window ended, oldest first
     */
    public function searchesExpiredBefore(CarbonInterface $now, int $limit): array;

    /**
     * @return list<int> ids of draft scheduled bookings whose search opens at or before `$opensBefore`
     */
    public function scheduledDueBefore(CarbonInterface $opensBefore, int $limit): array;

    /**
     * Requests a provider may still answer: dispatched to them, still searching, window open, and not declined or
     * expired for them. Newest dispatch first.
     *
     * @return Collection<int, Booking>
     */
    public function openRequestsForProvider(int $providerProfileId, CarbonInterface $now, int $limit): Collection;

    /**
     * A booking the provider was selected for.
     */
    public function findAssignedToProvider(string $uuid, int $providerProfileId): ?Booking;

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

    /**
     * Rows of the admin Bookings listing, filtered (Plan Appendix C). Selects the listing columns only; contact data is
     * read through the `customer` and `provider.user` relations and shown masked.
     *
     * @return Builder<Booking>
     */
    public function adminDatatableQuery(BookingAdminFilterData $filters): Builder;

    /**
     * The no-offer queue: bookings still searching since before `$searchingBefore` without a provider, plus bookings
     * that ended as `expired_no_provider` since `$expiredSince`.
     *
     * @return Builder<Booking>
     */
    public function noOfferQueueQuery(CarbonInterface $searchingBefore, CarbonInterface $expiredSince): Builder;

    /**
     * A page of the provider's jobs for the driver app, newest first (bookings the provider holds or held to the end).
     *
     * @return LengthAwarePaginator<int, Booking>
     */
    public function jobHistoryForProvider(ProviderProfile $profile, int $perPage): LengthAwarePaginator;

    /**
     * Completed jobs of the provider and the sum of their final fares per currency (gross, before any commission).
     *
     * @return list<array{currency: string, jobs: int, gross_minor: int}>
     */
    public function completedTotalsForProvider(int $providerProfileId): array;

    /**
     * The booking with every relation the admin show page needs.
     */
    public function findForAdmin(string $uuid): ?Booking;
}
