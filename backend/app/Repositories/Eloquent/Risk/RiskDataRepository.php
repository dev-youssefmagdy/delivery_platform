<?php

namespace App\Repositories\Eloquent\Risk;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\OfferStatus;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class RiskDataRepository implements RiskDataRepositoryInterface
{
    public function cancellationStats(RiskSubjectType $subject, CarbonInterface $since, int $minBookings): array
    {
        $isProvider = $subject === RiskSubjectType::Provider;
        $column = $isProvider ? 'provider_id' : 'customer_id';
        $cancelled = $isProvider ? BookingStatus::CancelledByProvider : BookingStatus::CancelledByCustomer;

        $rows = DB::table('bookings')
            ->selectRaw("{$column} as subject_id, count(*) as total, sum(case when status = ? then 1 else 0 end) as cancelled", [$cancelled->value])
            ->whereNotNull($column)
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', [BookingStatus::Draft->value, BookingStatus::ExpiredNoProvider->value, BookingStatus::CancelledByOperations->value])
            ->groupBy($column)
            ->havingRaw('count(*) >= ?', [$minBookings])
            ->get();

        return $rows->map(static fn (object $row): array => ['subject_id' => (int) $row->subject_id, 'total' => (int) $row->total, 'cancelled' => (int) $row->cancelled])->all();
    }

    public function noShowCounts(RiskSubjectType $subject, CarbonInterface $since, int $minEvents): array
    {
        $isProvider = $subject === RiskSubjectType::Provider;
        $column = $isProvider ? 'provider_id' : 'customer_id';

        $rows = DB::table('bookings')
            ->selectRaw("{$column} as subject_id, count(*) as events")
            ->whereNotNull($column)
            ->where('status', ($isProvider ? BookingStatus::CancelledByCustomer : BookingStatus::CancelledByProvider)->value)
            ->where('cancellation_reason', ($isProvider ? CancellationReason::DriverNoShow : CancellationReason::CustomerNoShow)->value)
            ->where('cancelled_at', '>=', $since)
            ->groupBy($column)
            ->havingRaw('count(*) >= ?', [$minEvents])
            ->get();

        return $rows->map(static fn (object $row): array => ['subject_id' => (int) $row->subject_id, 'events' => (int) $row->events])->all();
    }

    public function offerStats(CarbonInterface $since, int $minOffers): array
    {
        $rows = DB::table('booking_offers')
            ->selectRaw('provider_profile_id as subject_id, count(*) as offers, sum(case when winner_marker = 1 then 1 else 0 end) as accepted, sum(case when status = ? then 1 else 0 end) as withdrawn, sum(revision_count) as revisions', [OfferStatus::WithdrawnByProvider->value])
            ->where('created_at', '>=', $since)
            ->groupBy('provider_profile_id')
            ->havingRaw('count(*) >= ?', [$minOffers])
            ->get();

        return $rows->map(static fn (object $row): array => [
            'subject_id' => (int) $row->subject_id, 'offers' => (int) $row->offers, 'accepted' => (int) $row->accepted,
            'withdrawn' => (int) $row->withdrawn, 'revisions' => (int) $row->revisions,
        ])->all();
    }

    public function locationAnomalies(CarbonInterface $since, int $minEvents): array
    {
        $rows = DB::table('provider_locations')
            ->selectRaw('provider_profile_id as subject_id, anomaly, count(*) as events')
            ->whereNotNull('anomaly')
            ->where('recorded_at', '>=', $since)
            ->groupBy('provider_profile_id', 'anomaly')
            ->get();

        $bySubject = [];

        foreach ($rows as $row) {
            $bySubject[(int) $row->subject_id][(string) $row->anomaly] = (int) $row->events;
        }

        $result = [];

        foreach ($bySubject as $subjectId => $byType) {
            $events = array_sum($byType);

            if ($events >= $minEvents) {
                $result[] = ['subject_id' => $subjectId, 'events' => $events, 'by_type' => $byType];
            }
        }

        return $result;
    }

    public function sharedDevices(CarbonInterface $since, int $minAccounts): array
    {
        $devices = DB::table('user_devices')
            ->select('app', 'device_id')
            ->where(static fn ($query) => $query->where('last_active_at', '>=', $since)->orWhere('created_at', '>=', $since))
            ->groupBy('app', 'device_id')
            ->havingRaw('count(distinct user_id) >= ?', [$minAccounts])
            ->limit(500)
            ->get();

        $result = [];

        foreach ($devices as $device) {
            $userIds = DB::table('user_devices')->where('app', $device->app)->where('device_id', $device->device_id)->pluck('user_id')->map(static fn (mixed $id): int => (int) $id)->unique()->values()->all();
            $result[] = ['device_ref' => substr(hash('sha256', (string) config('app.key').'|'.$device->device_id), 0, 12), 'app' => (string) $device->app, 'user_ids' => $userIds];
        }

        return $result;
    }

    public function paymentDisputeCounts(RiskSubjectType $subject, CarbonInterface $since, int $minEvents): array
    {
        $isProvider = $subject === RiskSubjectType::Provider;
        $bookingColumn = $isProvider ? 'provider_id' : 'customer_id';
        $counts = [];

        $disputed = DB::table('bookings')->selectRaw("{$bookingColumn} as subject_id, count(*) as events")->whereNotNull($bookingColumn)
            ->where('status', BookingStatus::Disputed->value)->where('updated_at', '>=', $since)->groupBy($bookingColumn)->get();

        foreach ($disputed as $row) {
            $counts[(int) $row->subject_id]['disputed'] = (int) $row->events;
        }

        if (! $isProvider) {
            $refunds = DB::table('payment_refunds')->join('payments', 'payments.id', '=', 'payment_refunds.payment_id')
                ->selectRaw('payments.user_id as subject_id, count(*) as events')->where('payment_refunds.created_at', '>=', $since)->groupBy('payments.user_id')->get();

            foreach ($refunds as $row) {
                $counts[(int) $row->subject_id]['refunds'] = (int) $row->events;
            }

            $failed = DB::table('payments')->selectRaw('user_id as subject_id, count(*) as events')->where('status', PaymentStatus::Failed->value)
                ->where('created_at', '>=', $since)->groupBy('user_id')->get();

            foreach ($failed as $row) {
                $counts[(int) $row->subject_id]['failed_payments'] = (int) $row->events;
            }
        }

        $result = [];

        foreach ($counts as $subjectId => $row) {
            $entry = ['subject_id' => $subjectId, 'disputed' => $row['disputed'] ?? 0, 'refunds' => $row['refunds'] ?? 0, 'failed_payments' => $row['failed_payments'] ?? 0];

            if ($entry['disputed'] + $entry['refunds'] + $entry['failed_payments'] >= $minEvents) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    public function providerProfileIdOfUser(int $userId): ?int
    {
        $id = DB::table('provider_profiles')->where('user_id', $userId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function sampleBookingCodes(RiskSubjectType $subject, int $subjectId, array $statuses, CarbonInterface $since, int $limit = 10): array
    {
        return DB::table('bookings')
            ->where($subject === RiskSubjectType::Provider ? 'provider_id' : 'customer_id', $subjectId)
            ->whereIn('status', $statuses)
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('code')
            ->map(static fn (mixed $code): string => (string) $code)
            ->all();
    }
}
