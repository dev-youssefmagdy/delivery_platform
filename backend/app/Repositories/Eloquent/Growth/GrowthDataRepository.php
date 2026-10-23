<?php

namespace App\Repositories\Eloquent\Growth;

use App\Enums\Booking\BookingStatus;
use App\Enums\Finance\EntrySide;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSubjectType;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class GrowthDataRepository implements GrowthDataRepositoryInterface
{
    public function markBookingProcessed(int $bookingId): bool
    {
        return DB::table('growth_processed_bookings')->insertOrIgnore(['booking_id' => $bookingId, 'processed_at' => now()]) === 1;
    }

    public function deviceIds(int $userId): array
    {
        return DB::table('user_devices')->where('user_id', $userId)->pluck('device_id')->map(static fn (mixed $id): string => (string) $id)->unique()->values()->all();
    }

    public function usersOnDevices(array $deviceIds, int $exceptUserId): array
    {
        if ($deviceIds === []) {
            return [];
        }

        return DB::table('user_devices')->whereIn('device_id', $deviceIds)->where('user_id', '!=', $exceptUserId)->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)->unique()->values()->all();
    }

    public function paymentCardKeys(int $userId): array
    {
        return DB::table('payment_methods')->where('user_id', $userId)->whereNull('deleted_at')->whereNotNull('last4')->get(['gateway', 'brand', 'last4'])
            ->map(static fn (object $row): string => $row->gateway.'|'.$row->brand.'|'.$row->last4)->unique()->values()->all();
    }

    public function hasOpenRiskSignal(RiskSubjectType $subject, int $subjectId, array $types): bool
    {
        return RiskSignal::query()->where('subject_type', $subject->value)->where('subject_id', $subjectId)->whereIn('type', $types)
            ->whereIn('status', RiskSignalStatus::activeValues())
            ->exists();
    }

    public function walletDebitedMinor(int $ledgerAccountId, CarbonInterface $since, array $exceptTypes): int
    {
        return (int) DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.ledger_account_id', $ledgerAccountId)
            ->where('journal_lines.side', EntrySide::Debit->value)
            ->where('journal_entries.posted_at', '>=', $since)
            ->when($exceptTypes !== [], static fn ($query) => $query->whereNotIn('journal_entries.type', $exceptTypes))
            ->sum('journal_lines.amount_minor');
    }

    public function user(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function providerProfile(int $id): ?ProviderProfile
    {
        return ProviderProfile::query()->find($id);
    }

    public function userOfProvider(int $providerProfileId): ?User
    {
        return User::query()->whereHas('providerProfile', static fn ($query) => $query->whereKey($providerProfileId))->first();
    }

    public function completedBookingsOfCustomer(int $customerId): int
    {
        return DB::table('bookings')->where('customer_id', $customerId)->where('status', BookingStatus::Completed->value)->count();
    }
}
