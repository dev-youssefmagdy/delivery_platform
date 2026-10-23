<?php

namespace App\Services\Risk;

use App\DTOs\Customers\SuspendCustomerData;
use App\DTOs\Provider\SuspendProviderData;
use App\Enums\Common\AuditAction;
use App\Enums\Risk\RiskAction;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSubjectType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Customers\CustomerAccountService;
use App\Services\Provider\ProviderAccountService;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * What staff can do with a risk signal (SRS §15, §16.12): suspend, restrict, dismiss or escalate, each with a mandatory reason and an
 * audit entry. Suspend and restrict go through the existing account services (their own audit, status history and notifications);
 * they are TEMPORARY only (a provider suspension from a signal is never permanent, an open-ended one is lifted with "reactivate"), and a
 * permanent decision stays a separate, deliberate action of the provider module.
 */
final class RiskSignalActionService
{
    public function __construct(
        private readonly RiskSignalRepositoryInterface $signals,
        private readonly RiskSubjectResolver $subjects,
        private readonly CustomerAccountService $customers,
        private readonly ProviderAccountService $providers,
        private readonly RiskNotifier $notifier,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `risk_signal_not_active`, `risk_action_invalid`, `risk_subject_missing`
     */
    public function act(RiskSignal $signal, Admin $actor, RiskAction $action, string $reason, ?CarbonImmutable $expiresAt = null): RiskSignal
    {
        $escalated = false;
        $result = $this->db->transaction(function () use ($signal, $actor, $action, $reason, $expiresAt, &$escalated): RiskSignal {
            $locked = $this->signals->lockById($signal->id) ?? throw (new ModelNotFoundException)->setModel(RiskSignal::class);

            if (! $locked->status->isActive()) {
                throw BusinessRuleViolation::make('risk_signal_not_active');
            }

            $before = ['status' => $locked->status->value];

            match ($action) {
                RiskAction::Suspend => $this->suspend($locked, $actor, $reason, $expiresAt),
                RiskAction::Restrict => $this->restrict($locked, $actor, $reason),
                RiskAction::Dismiss, RiskAction::Escalate => null,
            };

            if ($action === RiskAction::Escalate) {
                if ($locked->status === RiskSignalStatus::Escalated) {
                    throw BusinessRuleViolation::make('risk_action_invalid');
                }

                $this->signals->update($locked, ['status' => RiskSignalStatus::Escalated, 'escalated_at' => now(), 'resolution_reason' => $reason]);
                $escalated = true;
            } else {
                $this->signals->update($locked, [
                    'status' => $action === RiskAction::Dismiss ? RiskSignalStatus::Dismissed : RiskSignalStatus::Actioned, 'active_marker' => null,
                    'resolved_by_admin_id' => $actor->id, 'resolved_at' => now(), 'resolution_action' => $action, 'resolution_reason' => $reason,
                ]);
            }

            $this->audit->record(
                $action === RiskAction::Dismiss ? AuditAction::Rejected : ($action === RiskAction::Escalate ? AuditAction::Escalated : AuditAction::Resolved),
                $locked,
                $actor,
                before: $before,
                after: ['status' => $locked->status->value, 'action' => $action->value],
                reason: $reason,
                meta: ['type' => $locked->type->value, 'subject_type' => $locked->subject_type->value, 'subject_id' => $locked->subject_id, 'score' => $locked->score],
            );

            return $locked;
        });

        if ($escalated) {
            $this->notifier->signalsRaised($result->type, 1, 'escalated:'.$result->uuid, $result->uuid);
        }

        return $result;
    }

    private function suspend(RiskSignal $signal, Admin $actor, string $reason, ?CarbonImmutable $expiresAt): void
    {
        $subject = $this->subjects->describe($signal)['model'] ?? throw BusinessRuleViolation::make('risk_subject_missing');

        if ($signal->subject_type === RiskSubjectType::Provider && $subject instanceof ProviderProfile) {
            $this->providers->suspend($subject, new SuspendProviderData($reason, false, $expiresAt), $actor);
        } elseif ($subject instanceof User) {
            $this->customers->suspend($subject, new SuspendCustomerData($reason, $expiresAt), $actor);
        } else {
            throw BusinessRuleViolation::make('risk_subject_missing');
        }
    }

    private function restrict(RiskSignal $signal, Admin $actor, string $reason): void
    {
        $subject = $this->subjects->describe($signal)['model'] ?? throw BusinessRuleViolation::make('risk_subject_missing');

        // Providers have no "restricted" state; their stronger step is a temporary suspension.
        if (! $subject instanceof User || $signal->subject_type !== RiskSubjectType::Customer) {
            throw BusinessRuleViolation::make('risk_action_invalid');
        }

        $this->customers->restrict($subject, $reason, $actor);
    }
}
