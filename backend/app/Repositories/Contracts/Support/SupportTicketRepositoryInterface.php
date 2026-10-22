<?php

namespace App\Repositories\Contracts\Support;

use App\DTOs\Support\TicketFilterData;
use App\DTOs\Support\TicketVisibilityScope;
use App\Enums\Identity\ClientApp;
use App\Models\SupportTicket;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every query on `support_tickets`.
 *
 * @extends BaseRepositoryInterface<SupportTicket>
 */
interface SupportTicketRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Inserts the ticket and sets its human number (TK-000123) in the same call.
     *
     * @param array<string, mixed> $attributes
     */
    public function createNumbered(array $attributes): SupportTicket;

    public function findOfUser(string $uuid, int $userId, ClientApp $app): ?SupportTicket;

    /**
     * @return Builder<SupportTicket> the person's own tickets, newest activity first
     */
    public function userQuery(int $userId, ClientApp $app, ?string $status): Builder;

    public function countActiveOfUser(int $userId): int;

    public function countCreatedSince(int $userId, CarbonInterface $since): int;

    /**
     * An existing ticket of this source for the booking and person (one appeal per booking).
     */
    public function findForBookingAndSource(int $bookingId, int $userId, string $source): ?SupportTicket;

    /**
     * Admin inbox rows, restricted to what the admin may see.
     *
     * @return Builder<SupportTicket>
     */
    public function datatableQuery(TicketFilterData $filters, TicketVisibilityScope $scope): Builder;

    /**
     * @return Collection<int, SupportTicket> tickets (all visibilities) linked to a booking, newest first
     */
    public function forBooking(int $bookingId): Collection;

    /**
     * @return Collection<int, SupportTicket> tickets of a person (any app), for the personal data export
     */
    public function allOfUser(int $userId): Collection;

    /**
     * Active tickets assigned per admin (workload for auto-assignment).
     *
     * @param list<int> $adminIds
     * @return array<int, int> admin id => count
     */
    public function activeCountsByAdmin(array $adminIds): array;

    /**
     * @return Collection<int, SupportTicket> active tickets past their first-response due time and not flagged yet
     */
    public function slaOverdue(CarbonInterface $now, int $limit): Collection;

    /**
     * @return Collection<int, SupportTicket> resolved tickets older than the cutoff (to close automatically)
     */
    public function resolvedBefore(CarbonInterface $cutoff, int $limit): Collection;

    /**
     * Anonymises the person's tickets (account deletion): subject and message bodies are removed.
     *
     * @return list<string> attachment paths to delete from disk
     */
    public function scrubOfUser(int $userId): array;

    /**
     * Internal id of a payment the user made (payments are owned by `user_id`), or null.
     */
    public function resolveOwnedPaymentId(int $userId, string $paymentUuid): ?int;

    /**
     * Internal id of a payout request of the provider profile, or null.
     */
    public function resolveOwnedPayoutId(int $providerProfileId, string $payoutUuid): ?int;

    public function countReportsSince(int $bookingId, int $userId, CarbonInterface $since): int;

    public function providerProfileIdOfUser(int $userId): ?int;

    public function bookingHasActiveTicket(int $bookingId): bool;
}
