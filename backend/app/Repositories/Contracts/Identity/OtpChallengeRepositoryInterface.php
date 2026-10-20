<?php

namespace App\Repositories\Contracts\Identity;

use App\DTOs\Identity\OtpChallengeFilterData;
use App\Enums\Identity\OtpPurpose;
use App\Models\OtpChallenge;
use Illuminate\Database\Eloquent\Builder;

interface OtpChallengeRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): OtpChallenge;

    public function latestFor(string $phoneHash, OtpPurpose $purpose): ?OtpChallenge;

    /**
     * Newest challenge of the phone with a row lock. Inside a transaction.
     */
    public function lockLatestFor(string $phoneHash, OtpPurpose $purpose): ?OtpChallenge;

    /**
     * Marks every still-open challenge of the phone as superseded.
     */
    public function invalidateOpen(string $phoneHash, OtpPurpose $purpose): int;

    public function incrementAttempts(OtpChallenge $challenge): OtpChallenge;

    public function markConsumed(OtpChallenge $challenge): OtpChallenge;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(OtpChallenge $challenge, array $attributes): OtpChallenge;

    /**
     * @return Builder<OtpChallenge>
     */
    public function datatableQuery(OtpChallengeFilterData $filters): Builder;
}
