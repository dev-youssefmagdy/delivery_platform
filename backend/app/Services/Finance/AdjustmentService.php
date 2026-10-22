<?php

namespace App\Services\Finance;

use App\DTOs\Finance\AdjustmentRequestData;
use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\AdjustmentDirection;
use App\Enums\Finance\ApprovalStatus;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Admin;
use App\Models\FinancialAdjustmentRequest;
use App\Models\LedgerAccount;
use App\Repositories\Contracts\Finance\FinancialAdjustmentRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Manual wallet adjustments as maker-checker (SRS §12.3): one admin (`wallets.adjust`) asks with a reason; ANOTHER admin
 * (`wallets.approve_adjustment`, never the requester) approves, and only then a new ledger entry is posted (platform
 * `adjustments` account against the wallet). An existing entry is never edited. Rejecting needs a reason; the requester may
 * cancel their own pending request. A debit that would take a customer wallet below zero is refused by the ledger policy.
 */
final class AdjustmentService
{
    public function __construct(
        private readonly FinancialAdjustmentRepositoryInterface $requests,
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly LedgerAccountResolver $resolver,
        private readonly LedgerService $ledger,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function request(Admin $actor, AdjustmentRequestData $data): FinancialAdjustmentRequest
    {
        $existing = $this->requests->findByIdempotencyKey($data->idempotencyKey);

        if ($existing instanceof FinancialAdjustmentRequest) {
            return $this->replay($existing, $data);
        }

        $account = $this->accounts->find($data->accountId);

        if (! $account instanceof LedgerAccount || ! $account->type->isWallet()) {
            throw BusinessRuleViolation::make('adjustment_account_not_wallet', 'ledger_account_id');
        }

        if ($data->amountMinor <= 0) {
            throw BusinessRuleViolation::make('adjustment_amount_invalid', 'amount_minor');
        }

        try {
            return $this->db->transaction(function () use ($actor, $data, $account): FinancialAdjustmentRequest {
                $request = $this->requests->insert([
                    'ledger_account_id' => $account->id,
                    'direction' => $data->direction,
                    'amount_minor' => $data->amountMinor,
                    'currency' => $account->currency,
                    'status' => ApprovalStatus::Pending,
                    'reason' => $data->reason,
                    'reference' => $data->reference,
                    'requested_by_id' => $actor->id,
                    'idempotency_key' => $data->idempotencyKey,
                ]);

                $this->audit->record(AuditAction::Requested, $request, $actor, after: [
                    'account' => $account->uuid,
                    'direction' => $data->direction->value,
                    'amount_minor' => $data->amountMinor,
                    'currency' => $account->currency,
                ], reason: $data->reason);

                return $request;
            });
        } catch (UniqueConstraintViolationException) {
            $winner = $this->requests->findByIdempotencyKey($data->idempotencyKey);

            return $winner instanceof FinancialAdjustmentRequest ? $this->replay($winner, $data) : throw BusinessRuleViolation::make('idempotency_key_reused');
        }
    }

    public function approve(Admin $actor, FinancialAdjustmentRequest $request, ?string $note): FinancialAdjustmentRequest
    {
        return $this->db->transaction(function () use ($actor, $request, $note): FinancialAdjustmentRequest {
            $locked = $this->lock($request);

            if ($locked->status !== ApprovalStatus::Pending) {
                throw ConflictException::make('adjustment_not_pending');
            }

            if (! $actor->can(AdminPermission::WalletsApproveAdjustment->value)) {
                throw ForbiddenActionException::make('adjustment_approval_forbidden');
            }

            if ($locked->requested_by_id === $actor->id) {
                throw ForbiddenActionException::make('adjustment_self_approval');
            }

            $entry = $this->post($actor, $locked);
            $this->requests->update($locked, [
                'status' => ApprovalStatus::Approved,
                'decided_by_id' => $actor->id,
                'decided_at' => CarbonImmutable::now(),
                'decision_reason' => $note,
                'journal_entry_id' => $entry,
            ]);
            $this->audit->record(AuditAction::Approved, $locked, $actor, after: ['status' => ApprovalStatus::Approved->value, 'journal_entry_id' => $entry], reason: $note);

            return $locked;
        });
    }

    public function reject(Admin $actor, FinancialAdjustmentRequest $request, string $reason): FinancialAdjustmentRequest
    {
        return $this->db->transaction(function () use ($actor, $request, $reason): FinancialAdjustmentRequest {
            $locked = $this->lock($request);

            if ($locked->status !== ApprovalStatus::Pending) {
                throw ConflictException::make('adjustment_not_pending');
            }

            if (! $actor->can(AdminPermission::WalletsApproveAdjustment->value)) {
                throw ForbiddenActionException::make('adjustment_approval_forbidden');
            }

            $this->requests->update($locked, ['status' => ApprovalStatus::Rejected, 'decided_by_id' => $actor->id, 'decided_at' => CarbonImmutable::now(), 'decision_reason' => $reason]);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, after: ['status' => ApprovalStatus::Rejected->value], reason: $reason);

            return $locked;
        });
    }

    public function cancel(Admin $actor, FinancialAdjustmentRequest $request, string $reason): FinancialAdjustmentRequest
    {
        return $this->db->transaction(function () use ($actor, $request, $reason): FinancialAdjustmentRequest {
            $locked = $this->lock($request);

            if ($locked->status !== ApprovalStatus::Pending) {
                throw ConflictException::make('adjustment_not_pending');
            }

            if ($locked->requested_by_id !== $actor->id) {
                throw ForbiddenActionException::make('adjustment_cancel_forbidden');
            }

            $this->requests->update($locked, ['status' => ApprovalStatus::Cancelled, 'decided_by_id' => $actor->id, 'decided_at' => CarbonImmutable::now(), 'decision_reason' => $reason]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, after: ['status' => ApprovalStatus::Cancelled->value], reason: $reason);

            return $locked;
        });
    }

    /**
     * Posts the approved adjustment and returns the entry id. The wallet is locked by the ledger post itself.
     */
    private function post(Admin $actor, FinancialAdjustmentRequest $request): int
    {
        $account = $this->accounts->find($request->ledger_account_id) ?? throw new ModelNotFoundException;
        $counter = $this->resolver->platform(LedgerAccountType::Adjustments, $account->currency);
        $amount = Money::of($request->amount_minor, $this->currencies->get($account->currency));
        $credit = $request->direction === AdjustmentDirection::Credit;

        return $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::Adjustment,
            idempotencyKey: 'adjustment:'.$request->uuid,
            descriptionKey: 'ledger.entry.adjustment',
            lines: $credit
                ? [JournalLineData::credit($account, $amount), JournalLineData::debit($counter, $amount)]
                : [JournalLineData::debit($account, $amount), JournalLineData::credit($counter, $amount)],
            referenceType: 'financial_adjustment_request',
            referenceId: $request->id,
            meta: ['adjustment' => $request->uuid, 'direction' => $request->direction->value, 'reason' => $request->reason, 'reference' => $request->reference],
            actorType: ActorType::Admin,
            actorId: $actor->id,
        ))->entry->id;
    }

    private function lock(FinancialAdjustmentRequest $request): FinancialAdjustmentRequest
    {
        return $this->requests->lockById($request->id) ?? throw (new ModelNotFoundException)->setModel(FinancialAdjustmentRequest::class);
    }

    private function replay(FinancialAdjustmentRequest $existing, AdjustmentRequestData $data): FinancialAdjustmentRequest
    {
        if ($existing->ledger_account_id !== $data->accountId || $existing->amount_minor !== $data->amountMinor || $existing->direction !== $data->direction) {
            throw BusinessRuleViolation::make('idempotency_key_reused');
        }

        return $existing;
    }
}
