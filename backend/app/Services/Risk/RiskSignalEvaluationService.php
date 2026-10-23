<?php

namespace App\Services\Risk;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSignalType;
use App\Models\RiskSignal;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Services\Risk\Detectors\RiskDetector;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;

/**
 * Runs the detectors and keeps the signal queue in step with the data (SRS §15, ADR-174). Idempotent by design:
 *  - at most one ACTIVE signal exists per type and subject (unique index on `active_marker`), so a rerun refreshes score, evidence and
 *    `last_evaluated_at` of the same row instead of adding another;
 *  - a signal dismissed or actioned by staff is not raised again for `risk.cooldown_days`;
 *  - an OPEN signal whose subject no longer meets the threshold is closed by the system (`dismissed`, reason stated); an escalated one
 *    waits for a person;
 *  - nothing here ever suspends anybody: a signal is a review task, and ratings are never an input.
 */
final class RiskSignalEvaluationService
{
    /**
     * @param iterable<RiskDetector> $detectors
     */
    public function __construct(
        private readonly iterable $detectors,
        private readonly RiskSignalRepositoryInterface $signals,
        private readonly RiskNotifier $notifier,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return array{created: int, refreshed: int, skipped: int, closed: int}
     */
    public function run(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $since = $now->subDays(max(1, (int) config('risk.window_days', 30)));
        $totals = ['created' => 0, 'refreshed' => 0, 'skipped' => 0, 'closed' => 0];
        $createdByType = [];

        foreach ($this->detectors as $detector) {
            if (! $detector->enabled()) {
                continue;
            }

            $candidates = array_slice($detector->detect($since), 0, max(1, (int) config('risk.max_per_run', 500)));
            $flagged = [];

            foreach ($candidates as $candidate) {
                $flagged[$candidate->subjectType->value][] = $candidate->subjectId;
                $outcome = $this->upsert($candidate, $now);

                if ($outcome instanceof RiskSignal) {
                    $totals['created']++;
                    $createdByType[$detector->type()->value][] = $outcome->uuid;
                } else {
                    $totals[$outcome]++;
                }
            }

            foreach ($detector->subjectTypes() as $subjectType) {
                $totals['closed'] += $this->signals->autoCloseCleared($detector->type(), $subjectType, $flagged[$subjectType->value] ?? [], (string) __('reputation.risk.auto_closed'));
            }
        }

        foreach ($createdByType as $type => $uuids) {
            $this->notifier->signalsRaised(RiskSignalType::from($type), count($uuids), $uuids[0], count($uuids) === 1 ? $uuids[0] : null);
        }

        return $totals;
    }

    /**
     * @return RiskSignal|'refreshed'|'skipped' the created signal, or what happened to an existing one
     */
    private function upsert(RiskCandidate $candidate, CarbonImmutable $now): RiskSignal|string
    {
        try {
            return $this->db->transaction(function () use ($candidate, $now): RiskSignal|string {
                $existing = $this->signals->findActive($candidate->type, $candidate->subjectType, $candidate->subjectId);

                if ($existing !== null) {
                    $this->signals->update($existing, ['score' => $candidate->score, 'evidence' => $candidate->evidence, 'last_evaluated_at' => $now]);

                    return 'refreshed';
                }

                $cooldownStart = $now->subDays(max(0, (int) config('risk.cooldown_days', 14)));

                if ($this->signals->resolvedSince($candidate->type, $candidate->subjectType, $candidate->subjectId, $cooldownStart)) {
                    return 'skipped';
                }

                return $this->signals->create([
                    'type' => $candidate->type, 'subject_type' => $candidate->subjectType, 'subject_id' => $candidate->subjectId, 'score' => $candidate->score,
                    'evidence' => $candidate->evidence, 'status' => RiskSignalStatus::Open, 'active_marker' => 1,
                    'first_detected_at' => $now, 'last_evaluated_at' => $now,
                ]);
            });
        } catch (QueryException $e) {
            // Two runs raced for the same new signal: the unique index let one win; this one found nothing to add.
            if (str_contains($e->getMessage(), 'risk_signals_one_active_per_subject')) {
                return 'skipped';
            }

            throw $e;
        }
    }
}
