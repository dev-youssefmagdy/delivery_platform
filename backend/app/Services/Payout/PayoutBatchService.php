<?php

namespace App\Services\Payout;

use App\Enums\Common\AuditAction;
use App\Enums\Payout\PayoutBatchStatus;
use App\Enums\Payout\PayoutStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\PayoutBatch;
use App\Models\PayoutRequest;
use App\Models\ProviderPayoutMethod;
use App\Repositories\Contracts\Payout\PayoutBatchRepositoryInterface;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Payout batches (a transfer run). Creating a batch takes approved payouts of one currency, moves them to processing and groups
 * them; exporting the batch produces the transfer file (the only place the decrypted destination leaves the server: permissioned,
 * streamed, never written to disk, audited) and marks the batch exported.
 */
final class PayoutBatchService
{
    public function __construct(
        private readonly PayoutBatchRepositoryInterface $batches,
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly PayoutHistory $history,
        private readonly AuditLogger $audit,
        private readonly CurrencyRegistry $currencies,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @param list<int> $payoutIds
     */
    public function create(Admin $actor, array $payoutIds, ?string $notes): PayoutBatch
    {
        $payoutIds = array_values(array_unique($payoutIds));

        if ($payoutIds === []) {
            throw BusinessRuleViolation::make('payout_batch_empty', 'payout_ids');
        }

        return $this->db->transaction(function () use ($actor, $payoutIds, $notes): PayoutBatch {
            $locked = $this->payouts->lockMany($payoutIds);

            if ($locked->count() !== count($payoutIds)) {
                throw (new ModelNotFoundException)->setModel(PayoutRequest::class);
            }

            if ($locked->contains(static fn (PayoutRequest $payout): bool => $payout->status !== PayoutStatus::Approved || $payout->payout_batch_id !== null)) {
                throw ConflictException::make('payout_batch_not_approved');
            }

            $currencies = $locked->pluck('currency')->unique();

            if ($currencies->count() !== 1) {
                throw BusinessRuleViolation::make('payout_batch_mixed_currency', 'payout_ids');
            }

            $batch = $this->batches->insert([
                'reference' => 'PB'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'status' => PayoutBatchStatus::Open,
                'currency' => (string) $currencies->first(),
                'payout_count' => $locked->count(),
                'total_minor' => (int) $locked->sum('net_minor'),
                'notes' => $notes,
                'created_by_id' => $actor->id,
            ]);

            $now = CarbonImmutable::now();

            foreach ($locked as $payout) {
                $this->payouts->update($payout, ['status' => PayoutStatus::Processing, 'payout_batch_id' => $batch->id, 'processing_by_id' => $actor->id, 'processing_at' => $now]);
                $this->history->record($payout, PayoutStatus::Approved, PayoutStatus::Processing, $actor, $notes, ['batch' => $batch->reference]);
            }

            $this->audit->record(AuditAction::Created, $batch, $actor, after: ['reference' => $batch->reference, 'payouts' => $locked->count(), 'total_minor' => $batch->total_minor], reason: $notes);

            return $batch;
        });
    }

    /**
     * The transfer file as rows (header first). Includes the decrypted destination, so the caller must hold `payouts.review`
     * and stream it straight to the response.
     *
     * @return list<list<string>>
     */
    public function exportRows(Admin $actor, PayoutBatch $batch): array
    {
        return $this->db->transaction(function () use ($actor, $batch): array {
            $locked = $this->batches->lockById($batch->id) ?? throw (new ModelNotFoundException)->setModel(PayoutBatch::class);
            $rows = [['reference', 'payout', 'provider', 'method', 'holder', 'bank', 'account_or_wallet', 'net_amount', 'currency']];

            foreach ($this->payouts->forBatch($locked->id) as $payout) {
                $method = $payout->method;
                $details = $method instanceof ProviderPayoutMethod ? $method->details : [];
                $rows[] = [
                    $locked->reference,
                    $payout->uuid,
                    (string) ($payout->provider->user->name ?? ''),
                    (string) ($payout->method_snapshot['type'] ?? ''),
                    (string) ($details['holder_name'] ?? ''),
                    (string) ($details['bank_name'] ?? ''),
                    (string) ($details['account_number'] ?? $details['wallet_number'] ?? ''),
                    Money::of($payout->net_minor, $this->currencies->get($payout->currency))->toDecimal(),
                    $payout->currency,
                ];
            }

            if ($locked->status === PayoutBatchStatus::Open) {
                $this->batches->update($locked, ['status' => PayoutBatchStatus::Exported, 'exported_at' => CarbonImmutable::now(), 'exported_by_id' => $actor->id]);
            }

            $this->audit->record(AuditAction::Exported, $locked, $actor, meta: ['reference' => $locked->reference, 'rows' => count($rows) - 1, 'contains_destination_details' => true]);

            return $rows;
        });
    }
}
