<?php

namespace App\Services\Finance;

use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\DTOs\Finance\JournalPostingResult;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\EntrySide;
use App\Enums\Finance\JournalEntryType;
use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * The only writer of the ledger (Plan I5, I6; SRS §12.3).
 *
 * post():
 * - validates the entry: at least two lines, positive amounts, and Σdebit = Σcredit in every currency;
 * - is idempotent by key: the same key and lines return the first entry (`replayed`), the same key with other lines is
 *   refused (409 `ledger_idempotency_key_reused`);
 * - in one transaction locks every account involved in ascending id order (one deterministic order for every caller, so
 *   concurrent postings cannot deadlock on each other), checks each account's currency and negative-balance policy,
 *   inserts the entry and its lines (with the running balance) and updates the cached balances;
 * - never updates or deletes an entry or a line: a mistake is corrected by reverse(), a compensating entry.
 *
 * Balances are in each account's natural sign: normal-side lines add, opposite-side lines subtract.
 */
final class LedgerService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly JournalLineRepositoryInterface $lines,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function post(JournalEntryData $data): JournalPostingResult
    {
        $this->assertWellFormed($data);

        $existing = $this->entries->findByIdempotencyKey($data->idempotencyKey);

        if ($existing instanceof JournalEntry) {
            return $this->replay($existing, $data);
        }

        try {
            return $this->db->transaction(fn (): JournalPostingResult => $this->postLocked($data));
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent post with the same key committed first.
            $winner = $this->entries->findByIdempotencyKey($data->idempotencyKey);

            if ($winner instanceof JournalEntry) {
                return $this->replay($winner, $data);
            }

            throw $exception;
        }
    }

    /**
     * Posts the compensating entry of $entry: same accounts and amounts, opposite sides, linked by `reversal_of_id`.
     * Reversing the same entry again returns the first reversal. A reversal cannot be reversed (post the original
     * business event again instead). Reversals bypass the negative-balance policy: correcting an error must not be
     * blocked because the original credit was already spent.
     */
    public function reverse(JournalEntry $entry, string $reason, ?Admin $actor = null): JournalPostingResult
    {
        return $this->db->transaction(function () use ($entry, $reason, $actor): JournalPostingResult {
            $locked = $this->entries->lockById($entry->id) ?? throw LedgerException::notReversible();

            if ($locked->reversal_of_id !== null) {
                throw LedgerException::notReversible();
            }

            $existing = $this->entries->reversalOf($locked->id);

            if ($existing instanceof JournalEntry) {
                return new JournalPostingResult($existing, true);
            }

            $result = $this->post(new JournalEntryData(
                type: JournalEntryType::Reversal,
                idempotencyKey: 'reversal:'.$locked->uuid,
                descriptionKey: 'ledger.entry.reversal',
                lines: $this->lines->forEntry($locked)->map(static fn ($line): JournalLineData => new JournalLineData($line->ledger_account_id, $line->side->opposite(), $line->amount ?? throw LedgerException::invalidLine()))->values()->all(),
                referenceType: $locked->reference_type,
                referenceId: $locked->reference_id,
                meta: ['reversal_of' => $locked->uuid, 'reason' => $reason],
                actorType: $actor instanceof Admin ? ActorType::Admin : ActorType::System,
                actorId: $actor?->id,
                enforceNegativePolicy: false,
                reversalOfId: $locked->id,
            ));

            if (! $result->replayed) {
                $this->audit->record(AuditAction::Reversed, $locked, $actor, reason: $reason, meta: ['reversal' => $result->entry->uuid]);
            }

            return $result;
        });
    }

    private function postLocked(JournalEntryData $data): JournalPostingResult
    {
        $accountIds = array_values(array_unique(array_map(static fn (JournalLineData $line): int => $line->accountId, $data->lines)));
        $accounts = $this->accounts->lockInOrder($accountIds);

        // The first plain read after the locks: a concurrent post of the same key has committed by now.
        $existing = $this->entries->findByIdempotencyKey($data->idempotencyKey);

        if ($existing instanceof JournalEntry) {
            return $this->replay($existing, $data);
        }

        $running = [];
        $rows = [];

        foreach ($data->lines as $line) {
            $account = $accounts->get($line->accountId) ?? throw LedgerException::unknownAccount($line->accountId);

            if ($line->amount->currency->code !== $account->currency) {
                throw LedgerException::currencyMismatch($account->currency, $line->amount->currency->code);
            }

            $running[$account->id] ??= $account->balance_minor;
            $running[$account->id] += $line->side === $account->type->normalSide() ? $line->amount->amount : -$line->amount->amount;

            $rows[] = [
                'ledger_account_id' => $account->id,
                'side' => $line->side->value,
                'amount_minor' => $line->amount->amount,
                'currency' => $account->currency,
                'balance_after_minor' => $running[$account->id],
            ];
        }

        $this->assertPolicy($data, $accounts, $running);

        $entry = $this->entries->insert([
            'type' => $data->type,
            'reference_type' => $data->referenceType,
            'reference_id' => $data->referenceId,
            'description_key' => $data->descriptionKey,
            'meta' => $data->meta === [] ? null : $data->meta,
            'posted_at' => $data->postedAt ?? CarbonImmutable::now(),
            'reversal_of_id' => $data->reversalOfId,
            'created_by_type' => $data->actorType?->value,
            'created_by_id' => $data->actorId,
            'idempotency_key' => $data->idempotencyKey,
            'fingerprint' => $data->fingerprint(),
        ]);

        $this->lines->insertFor($entry, $rows);

        ksort($running);

        foreach ($running as $accountId => $balance) {
            $this->accounts->writeBalance($accounts->get($accountId) ?? throw LedgerException::unknownAccount($accountId), $balance);
        }

        return new JournalPostingResult($entry);
    }

    /**
     * Negative-balance policy per account: an account that does not allow negatives (or has a credit limit) may not end
     * below its lowest allowed balance. An account already below it may still receive money.
     *
     * @param Collection<int, LedgerAccount> $accounts
     * @param array<int, int> $final
     */
    private function assertPolicy(JournalEntryData $data, Collection $accounts, array $final): void
    {
        foreach ($final as $accountId => $balance) {
            $account = $accounts->get($accountId) ?? throw LedgerException::unknownAccount($accountId);
            $lowest = in_array($accountId, $data->nonNegativeAccountIds, true)
                ? 0
                : ($data->enforceNegativePolicy ? $account->lowestAllowedBalance() : null);

            if ($lowest !== null && $balance < $lowest && $balance < $account->balance_minor) {
                throw LedgerException::negativeBalance($account->uuid, $balance, $lowest);
            }
        }
    }

    private function assertWellFormed(JournalEntryData $data): void
    {
        if (count($data->lines) < 2 || $data->idempotencyKey === '' || strlen($data->idempotencyKey) > 120) {
            throw LedgerException::emptyEntry();
        }

        $totals = [];

        foreach ($data->lines as $line) {
            if (! $line->amount->isPositive()) {
                throw LedgerException::invalidLine();
            }

            $code = $line->amount->currency->code;
            $totals[$code] ??= ['debit' => 0, 'credit' => 0];
            $totals[$code][$line->side === EntrySide::Debit ? 'debit' : 'credit'] += $line->amount->amount;
        }

        foreach ($totals as $currency => $sum) {
            if ($sum['debit'] !== $sum['credit']) {
                throw LedgerException::unbalanced($currency, $sum['debit'], $sum['credit']);
            }
        }
    }

    private function replay(JournalEntry $existing, JournalEntryData $data): JournalPostingResult
    {
        if ($existing->fingerprint !== $data->fingerprint()) {
            throw LedgerException::idempotencyKeyReused($data->idempotencyKey);
        }

        return new JournalPostingResult($existing, true);
    }
}
