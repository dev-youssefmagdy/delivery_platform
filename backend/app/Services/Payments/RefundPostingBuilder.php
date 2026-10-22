<?php

namespace App\Services\Payments;

use App\DTOs\Finance\JournalLineData;
use App\Enums\Finance\EntrySide;
use App\Support\Money\Money;

/**
 * Compensating lines of a refund (Plan §7.4, I5): the original settlement entry is never touched, a new entry moves
 * money back in the opposite direction. Pure: no database, no clock.
 *
 * Full refund: every original line reversed. Partial refund of R out of the collected P: the gateway clearing line is
 * exactly R (the money that really goes back to the customer); the other lines of the original entry are scaled so the
 * entry still balances and no minor unit is lost. Debit-side others (promo expense) are scaled by R/P and spread over
 * their lines; the credit side (provider wallet, revenue, taxes) takes the same total and is spread proportionally.
 */
final class RefundPostingBuilder
{
    /**
     * @param list<JournalLineData> $original lines of the settlement entry
     * @return list<JournalLineData>
     */
    public function reverse(array $original, int $gatewayAccountId, Money $collected, Money $refund): array
    {
        if ($refund->equals($collected)) {
            return array_values(array_map(static fn (JournalLineData $line): JournalLineData => $line->reversed(), $original));
        }

        $gatewayLine = null;
        $sameSide = [];
        $otherSide = [];

        foreach ($original as $line) {
            if ($line->accountId === $gatewayAccountId && $gatewayLine === null) {
                $gatewayLine = $line;
            }
        }

        if (! $gatewayLine instanceof JournalLineData) {
            throw new \LogicException('The settlement entry has no gateway clearing line to refund against.');
        }

        foreach ($original as $line) {
            if ($line === $gatewayLine) {
                continue;
            }

            if ($line->side === $gatewayLine->side) {
                $sameSide[] = $line;
            } else {
                $otherSide[] = $line;
            }
        }

        $sameTotal = array_sum(array_map(static fn (JournalLineData $line): int => $line->amount->amount, $sameSide));
        $scaledSame = $this->scale($sameTotal, $refund->amount, $collected->amount);
        $reversedSide = $gatewayLine->side->opposite();
        $lines = [new JournalLineData($gatewayAccountId, $reversedSide, $refund)];

        $this->spread($lines, $sameSide, $scaledSame, $reversedSide, $refund);
        $this->spread($lines, $otherSide, $refund->amount + $scaledSame, $reversedSide === EntrySide::Debit ? EntrySide::Credit : EntrySide::Debit, $refund);

        return $lines;
    }

    /**
     * round(total * refund / collected), half up, in integer arithmetic.
     */
    private function scale(int $total, int $refund, int $collected): int
    {
        return (int) bcdiv(bcadd(bcmul((string) (2 * $total), (string) $refund), (string) $collected), (string) (2 * $collected), 0);
    }

    /**
     * @param list<JournalLineData> $lines
     * @param list<JournalLineData> $source original lines whose accounts take a share
     */
    private function spread(array &$lines, array $source, int $amount, EntrySide $side, Money $like): void
    {
        if ($source === [] || $amount <= 0) {
            return;
        }

        $shares = Money::of($amount, $like->currency)->allocate(array_map(static fn (JournalLineData $line): int => $line->amount->amount, $source));

        foreach ($source as $index => $line) {
            if ($shares[$index]->isPositive()) {
                $lines[] = new JournalLineData($line->accountId, $side, $shares[$index]);
            }
        }
    }
}
