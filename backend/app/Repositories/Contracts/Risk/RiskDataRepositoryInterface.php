<?php

namespace App\Repositories\Contracts\Risk;

use App\Enums\Risk\RiskSubjectType;
use Carbon\CarbonInterface;

/**
 * Read model of the risk detectors (P5.2): aggregates over bookings, offers, provider locations, devices, payments and refunds. Only
 * counts and ids leave it; nothing is written.
 */
interface RiskDataRepositoryInterface
{
    /**
     * @return list<array{subject_id: int, total: int, cancelled: int}> subjects with at least `$minBookings` bookings since `$since`
     */
    public function cancellationStats(RiskSubjectType $subject, CarbonInterface $since, int $minBookings): array;

    /**
     * Customers marked as no-show by the provider (cancelled by the provider with reason customer_no_show) and providers reported as
     * no-show by the customer (cancelled by the customer with reason driver_no_show).
     *
     * @return list<array{subject_id: int, events: int}>
     */
    public function noShowCounts(RiskSubjectType $subject, CarbonInterface $since, int $minEvents): array;

    /**
     * @return list<array{subject_id: int, offers: int, accepted: int, withdrawn: int, revisions: int}>
     */
    public function offerStats(CarbonInterface $since, int $minOffers): array;

    /**
     * @return list<array{subject_id: int, events: int, by_type: array<string, int>}>
     */
    public function locationAnomalies(CarbonInterface $since, int $minEvents): array;

    /**
     * Devices used by several accounts of the same app. `device_ref` is a short hash, never the raw device id.
     *
     * @return list<array{device_ref: string, app: string, user_ids: list<int>}>
     */
    public function sharedDevices(CarbonInterface $since, int $minAccounts): array;

    /**
     * @return list<array{subject_id: int, disputed: int, refunds: int, failed_payments: int}> subjects with at least `$minEvents` disputed bookings, refunds and failed payments together
     */
    public function paymentDisputeCounts(RiskSubjectType $subject, CarbonInterface $since, int $minEvents): array;

    /**
     * Provider profile id of a user (account farm signals on the provider app), or null.
     */
    public function providerProfileIdOfUser(int $userId): ?int;

    /**
     * @param list<string> $statuses
     * @return list<string> newest first
     */
    public function sampleBookingCodes(RiskSubjectType $subject, int $subjectId, array $statuses, CarbonInterface $since, int $limit = 10): array;
}
