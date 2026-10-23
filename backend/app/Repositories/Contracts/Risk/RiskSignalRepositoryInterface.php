<?php

namespace App\Repositories\Contracts\Risk;

use App\DTOs\Risk\RiskSignalFilterData;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

interface RiskSignalRepositoryInterface
{
    public function findActive(RiskSignalType $type, RiskSubjectType $subjectType, int $subjectId): ?RiskSignal;

    /**
     * Whether a signal of this type and subject was dismissed or actioned since `$since` (cooldown: it is not raised again meanwhile).
     */
    public function resolvedSince(RiskSignalType $type, RiskSubjectType $subjectType, int $subjectId, CarbonInterface $since): bool;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): RiskSignal;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(RiskSignal $signal, array $attributes): RiskSignal;

    public function lockById(int $id): ?RiskSignal;

    public function findByUuid(string $uuid): ?RiskSignal;

    /**
     * Closes active signals whose subject no longer meets the detector's threshold (nothing is deleted).
     *
     * @param list<int> $stillFlaggedSubjectIds
     * @return int signals closed as `dismissed` by the system
     */
    public function autoCloseCleared(RiskSignalType $type, RiskSubjectType $subjectType, array $stillFlaggedSubjectIds, string $reason): int;

    /**
     * @return Builder<RiskSignal>
     */
    public function datatableQuery(RiskSignalFilterData $filters): Builder;

    /**
     * @return Builder<RiskSignal>
     */
    public function forSubjectQuery(RiskSubjectType $subjectType, int $subjectId): Builder;

    public function countActive(): int;

    public function activeCountForSubject(RiskSubjectType $subjectType, int $subjectId): int;

    public function customer(int $id): ?User;

    public function provider(int $id): ?ProviderProfile;

    /**
     * Account deletion: the facts of the signals about this subject are replaced by a marker; type, score, status and decision stay.
     */
    public function scrubOfSubject(RiskSubjectType $subjectType, int $subjectId): int;
}
