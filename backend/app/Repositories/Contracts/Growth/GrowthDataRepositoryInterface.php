<?php

namespace App\Repositories\Contracts\Growth;

use App\Enums\Risk\RiskSubjectType;
use Carbon\CarbonInterface;

/**
 * Read-only facts the growth fraud checks and the credit position need, plus the per-booking idempotency marker.
 */
interface GrowthDataRepositoryInterface
{
    /**
     * Inserts the marker for a completed booking. False when it was already there (the hook has handled this booking).
     */
    public function markBookingProcessed(int $bookingId): bool;

    /**
     * @return list<string> device ids the user signed in from (any app)
     */
    public function deviceIds(int $userId): array;

    /**
     * @param list<string> $deviceIds
     * @return list<int> other users that used one of these devices
     */
    public function usersOnDevices(array $deviceIds, int $exceptUserId): array;

    /**
     * @return list<string> "gateway|brand|last4" of the user's saved cards and wallets
     */
    public function paymentCardKeys(int $userId): array;

    /**
     * @param list<string> $types RiskSignalType values
     */
    public function hasOpenRiskSignal(RiskSubjectType $subject, int $subjectId, array $types): bool;

    /**
     * Money taken out of a wallet account since a moment (debit lines), ignoring the listed entry types.
     *
     * @param list<string> $exceptTypes JournalEntryType values
     */
    public function walletDebitedMinor(int $ledgerAccountId, CarbonInterface $since, array $exceptTypes): int;

    public function completedBookingsOfCustomer(int $customerId): int;
}
