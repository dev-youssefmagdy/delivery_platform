<?php

namespace App\Repositories\Eloquent\Risk;

use App\DTOs\Risk\RiskSignalFilterData;
use App\Enums\Risk\RiskAction;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class RiskSignalRepository implements RiskSignalRepositoryInterface
{
    public function findActive(RiskSignalType $type, RiskSubjectType $subjectType, int $subjectId): ?RiskSignal
    {
        return RiskSignal::query()
            ->where('type', $type->value)->where('subject_type', $subjectType->value)->where('subject_id', $subjectId)
            ->whereIn('status', RiskSignalStatus::activeValues())
            ->lockForUpdate()
            ->first();
    }

    public function resolvedSince(RiskSignalType $type, RiskSubjectType $subjectType, int $subjectId, CarbonInterface $since): bool
    {
        return RiskSignal::query()
            ->where('type', $type->value)->where('subject_type', $subjectType->value)->where('subject_id', $subjectId)
            ->whereIn('status', [RiskSignalStatus::Dismissed->value, RiskSignalStatus::Actioned->value])
            ->where('resolved_at', '>=', $since)
            ->exists();
    }

    public function create(array $attributes): RiskSignal
    {
        return RiskSignal::query()->create($attributes);
    }

    public function update(RiskSignal $signal, array $attributes): RiskSignal
    {
        $signal->forceFill($attributes)->save();

        return $signal;
    }

    public function lockById(int $id): ?RiskSignal
    {
        return RiskSignal::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findByUuid(string $uuid): ?RiskSignal
    {
        return RiskSignal::query()->where('uuid', $uuid)->first();
    }

    public function autoCloseCleared(RiskSignalType $type, RiskSubjectType $subjectType, array $stillFlaggedSubjectIds, string $reason): int
    {
        // Only OPEN signals close by themselves; an escalated one waits for a person.
        return RiskSignal::query()
            ->where('type', $type->value)->where('subject_type', $subjectType->value)
            ->where('status', RiskSignalStatus::Open->value)
            ->when($stillFlaggedSubjectIds !== [], static fn (Builder $q) => $q->whereNotIn('subject_id', $stillFlaggedSubjectIds))
            ->update([
                'status' => RiskSignalStatus::Dismissed->value, 'active_marker' => null, 'resolved_at' => now(),
                'resolution_action' => RiskAction::Dismiss->value, 'resolution_reason' => $reason, 'updated_at' => now(),
            ]);
    }

    public function datatableQuery(RiskSignalFilterData $filters): Builder
    {
        return RiskSignal::query()
            ->select('risk_signals.*')
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('risk_signals.status', array_map(static fn (RiskSignalStatus $case): string => $case->value, $filters->statuses)))
            ->when($filters->statuses === [], static fn (Builder $q) => $q->whereIn('risk_signals.status', RiskSignalStatus::activeValues()))
            ->when($filters->types !== [], static fn (Builder $q) => $q->whereIn('risk_signals.type', array_map(static fn (RiskSignalType $case): string => $case->value, $filters->types)))
            ->when($filters->subjectType !== null, static fn (Builder $q) => $q->where('risk_signals.subject_type', $filters->subjectType?->value))
            ->when($filters->minScore !== null, static fn (Builder $q) => $q->where('risk_signals.score', '>=', (int) $filters->minScore))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('risk_signals.first_detected_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('risk_signals.first_detected_at', '<=', $filters->to?->endOfDay()));
    }

    public function forSubjectQuery(RiskSubjectType $subjectType, int $subjectId): Builder
    {
        return RiskSignal::query()->select('risk_signals.*')->where('subject_type', $subjectType->value)->where('subject_id', $subjectId);
    }

    public function countActive(): int
    {
        return RiskSignal::query()->whereIn('status', RiskSignalStatus::activeValues())->count();
    }

    public function activeCountForSubject(RiskSubjectType $subjectType, int $subjectId): int
    {
        return RiskSignal::query()->where('subject_type', $subjectType->value)->where('subject_id', $subjectId)->whereIn('status', RiskSignalStatus::activeValues())->count();
    }

    public function customer(int $id): ?User
    {
        return User::query()->withTrashed()->find($id);
    }

    public function provider(int $id): ?ProviderProfile
    {
        return ProviderProfile::query()->with('user')->find($id);
    }

    public function scrubOfSubject(RiskSubjectType $subjectType, int $subjectId): int
    {
        return RiskSignal::query()->where('subject_type', $subjectType->value)->where('subject_id', $subjectId)
            ->update(['evidence' => json_encode(['scrubbed' => true], JSON_THROW_ON_ERROR), 'updated_at' => now()]);
    }
}
