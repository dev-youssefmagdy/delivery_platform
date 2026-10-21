<?php

namespace App\Repositories\Contracts\Booking;

use App\DTOs\Booking\OfferFilterData;
use App\DTOs\Operations\OfferStats;
use App\Enums\Booking\OfferStatus;
use App\Models\BookingOffer;
use App\Models\BookingOfferVersion;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Support\Money\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every query on `booking_offers` and `booking_offer_versions`. Locking reads must run inside a transaction that
 * already holds the booking's row lock (lock order is always booking, then offers).
 *
 * @extends BaseRepositoryInterface<BookingOffer>
 */
interface OfferRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function createOffer(array $attributes): BookingOffer;

    /**
     * Appends the next version (never edits one) and returns it.
     *
     * @param array<string, mixed>|null $scope
     */
    public function addVersion(BookingOffer $offer, Money $amount, ?string $note, ?array $scope, ?string $idempotencyKey): BookingOfferVersion;

    /**
     * Writes state columns of the offer row (status, expiry, current version ...), never an amount.
     *
     * @param array<string, mixed> $attributes
     */
    public function applyState(BookingOffer $offer, array $attributes): BookingOffer;

    public function lockOffer(int $id): ?BookingOffer;

    /**
     * Active offers of the booking, rows locked.
     *
     * @return Collection<int, BookingOffer>
     */
    public function lockActiveForBooking(int $bookingId): Collection;

    public function lockActiveFor(int $bookingId, int $providerProfileId): ?BookingOffer;

    /**
     * Every offer the provider ever made on the booking (any status): the per-request offer limit counts these.
     */
    public function countForProvider(int $bookingId, int $providerProfileId): int;

    public function findVersionByKey(int $providerProfileId, string $idempotencyKey): ?BookingOfferVersion;

    /**
     * The conditional update behind selection: accepts the offer only if it is still active and not past its expiry,
     * and marks it as the booking's one winner. The unique winner index makes a second winner impossible.
     *
     * @return bool whether this call accepted it
     */
    public function accept(BookingOffer $offer, CarbonInterface $now): bool;

    /**
     * The provider of the booking's accepted offer cancelled and the booking goes back to searching: frees the unique
     * winner slot so another offer can win, keeps the offer row and its versions as history (close reason
     * `provider_cancelled`). Call inside the transaction that holds the booking lock.
     *
     * @return int offers released (0 or 1)
     */
    public function releaseWinner(int $bookingId, CarbonInterface $now): int;

    /**
     * Closes the given offers that are still active.
     *
     * @param list<int> $ids
     * @return int rows closed
     */
    public function close(array $ids, OfferStatus $status, string $reason, CarbonInterface $now): int;

    /**
     * @return list<int>
     */
    public function expiredIds(CarbonInterface $now, int $limit): array;

    /**
     * @return list<int> booking ids where the provider has an active offer
     */
    public function activeBookingIdsForProvider(int $providerProfileId): array;

    /**
     * Offers the customer can choose right now (active and unexpired), cheapest first, with everything a card needs.
     *
     * @return Collection<int, BookingOffer>
     */
    public function openForCustomer(int $bookingId, CarbonInterface $now): Collection;

    /**
     * @return list<int> current amounts of other providers' open offers on the booking
     */
    public function openAmountsExcept(int $bookingId, int $exceptProviderProfileId, CarbonInterface $now): array;

    /**
     * @return array{count: int, lowest_minor: int|null}
     */
    public function openSummary(int $bookingId, CarbonInterface $now): array;

    /**
     * The provider's own offers, newest first.
     *
     * @return Collection<int, BookingOffer>
     */
    public function forProvider(int $providerProfileId, int $limit): Collection;

    /**
     * The provider's active offers on the given bookings, keyed by booking id.
     *
     * @param list<int> $bookingIds
     * @return Collection<int, BookingOffer>
     */
    public function activeOfProviderOn(int $providerProfileId, array $bookingIds): Collection;

    public function loadCard(BookingOffer $offer): BookingOffer;

    /**
     * Rows of the Offers monitor: each offer with its booking code, its current amount and the anomaly flag
     * (`very_low` / `very_high` against the reference estimate stored on the booking, or null).
     *
     * @return Builder<BookingOffer>
     */
    public function adminDatatableQuery(OfferFilterData $filters): Builder;

    /**
     * Conversion numbers for the same filters (submitted to selected, response rate, flagged).
     */
    public function statsFor(OfferFilterData $filters): OfferStats;

    /**
     * Every offer of the booking with all of its versions, oldest first, for the admin show page.
     *
     * @return Collection<int, BookingOffer>
     */
    public function allForBookingWithVersions(int $bookingId): Collection;
}
