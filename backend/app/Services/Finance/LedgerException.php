<?php

namespace App\Services\Finance;

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;

/**
 * Factory for the stable error codes of the ledger (`api.errors.ledger_*`). The ledger is internal money code: these
 * are programming or policy errors surfaced loudly, never swallowed.
 */
final class LedgerException
{
    public static function unbalanced(string $currency, int $debit, int $credit): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_unbalanced_entry', context: ['currency' => $currency, 'debit' => $debit, 'credit' => $credit]);
    }

    public static function emptyEntry(): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_entry_incomplete');
    }

    public static function invalidLine(): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_invalid_line');
    }

    public static function currencyMismatch(string $account, string $line): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_currency_mismatch', context: ['account' => $account, 'line' => $line]);
    }

    public static function negativeBalance(string $account, int $balance, int $lowest): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_negative_balance', context: ['account' => $account, 'balance' => $balance, 'lowest' => $lowest]);
    }

    public static function unknownAccount(int $id): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_unknown_account', context: ['account' => $id]);
    }

    public static function idempotencyKeyReused(string $key): ConflictException
    {
        return ConflictException::make('ledger_idempotency_key_reused', context: ['key' => $key]);
    }

    public static function alreadyReversed(string $uuid): ConflictException
    {
        return ConflictException::make('ledger_entry_already_reversed', context: ['entry' => $uuid]);
    }

    public static function notReversible(): BusinessRuleViolation
    {
        return BusinessRuleViolation::make('ledger_entry_not_reversible');
    }
}
