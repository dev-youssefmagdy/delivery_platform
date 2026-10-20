<?php

namespace App\Repositories\Eloquent\Identity;

use App\DTOs\Identity\OtpChallengeFilterData;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpChallengeState;
use App\Enums\Identity\OtpDeliveryStatus;
use App\Enums\Identity\OtpPurpose;
use App\Models\OtpChallenge;
use App\Repositories\Contracts\Identity\OtpChallengeRepositoryInterface;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

final class OtpChallengeRepository implements OtpChallengeRepositoryInterface
{
    public function __construct(private readonly OtpChallenge $model) {}

    public function create(array $attributes): OtpChallenge
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function latestFor(string $phoneHash, OtpPurpose $purpose): ?OtpChallenge
    {
        return $this->latestQuery($phoneHash, $purpose)->first();
    }

    public function lockLatestFor(string $phoneHash, OtpPurpose $purpose): ?OtpChallenge
    {
        return $this->latestQuery($phoneHash, $purpose)->lockForUpdate()->first();
    }

    public function invalidateOpen(string $phoneHash, OtpPurpose $purpose): int
    {
        return $this->model->newQuery()
            ->where('phone_hash', $phoneHash)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);
    }

    public function incrementAttempts(OtpChallenge $challenge): OtpChallenge
    {
        $this->model->newQuery()->whereKey($challenge->getKey())->increment('attempts');
        $challenge->attempts++;

        return $challenge;
    }

    public function markConsumed(OtpChallenge $challenge): OtpChallenge
    {
        $challenge->forceFill(['consumed_at' => now()])->save();

        return $challenge;
    }

    public function update(OtpChallenge $challenge, array $attributes): OtpChallenge
    {
        $challenge->forceFill($attributes)->save();

        return $challenge;
    }

    public function datatableQuery(OtpChallengeFilterData $filters): Builder
    {
        return $this->model->newQuery()
            ->when($filters->phone, function (Builder $query, string $phone): void {
                $query->where('phone_hash', PhoneNumber::hash($phone));
            })
            ->when($filters->states !== [], function (Builder $query) use ($filters): void {
                $query->where(function (Builder $inner) use ($filters): void {
                    foreach ($filters->states as $state) {
                        $inner->orWhere(fn (Builder $branch) => $this->applyState($branch, $state));
                    }
                });
            })
            ->when($filters->deliveries !== [], static fn (Builder $query) => $query->whereIn(
                'delivery_status',
                array_map(static fn (OtpDeliveryStatus $status): string => $status->value, $filters->deliveries),
            ))
            ->when($filters->apps !== [], static fn (Builder $query) => $query->whereIn(
                'app',
                array_map(static fn (ClientApp $app): string => $app->value, $filters->apps),
            ))
            ->when($filters->ip, static fn (Builder $query, string $ip) => $query->where('ip', $ip))
            ->when($filters->from, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->to, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    /**
     * Mirrors OtpChallenge::state() (first matching rule wins).
     *
     * @param Builder<OtpChallenge> $query
     */
    private function applyState(Builder $query, OtpChallengeState $state): void
    {
        match ($state) {
            OtpChallengeState::Verified => $query->whereNotNull('consumed_at'),
            OtpChallengeState::Superseded => $query->whereNull('consumed_at')->whereNotNull('invalidated_at'),
            OtpChallengeState::Locked => $query->whereNull('consumed_at')->whereNull('invalidated_at')->whereColumn('attempts', '>=', 'max_attempts'),
            OtpChallengeState::Expired => $query->whereNull('consumed_at')->whereNull('invalidated_at')->whereColumn('attempts', '<', 'max_attempts')->where('expires_at', '<=', now()),
            OtpChallengeState::Pending => $query->whereNull('consumed_at')->whereNull('invalidated_at')->whereColumn('attempts', '<', 'max_attempts')->where('expires_at', '>', now()),
        };
    }

    /**
     * @return Builder<OtpChallenge>
     */
    private function latestQuery(string $phoneHash, OtpPurpose $purpose): Builder
    {
        return $this->model->newQuery()
            ->where('phone_hash', $phoneHash)
            ->where('purpose', $purpose->value)
            ->orderByDesc('id')
            ->limit(1);
    }
}
