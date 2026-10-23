<?php

namespace App\Services\Finance;

use App\DTOs\Booking\CancellationDecision;
use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Common\ActorType;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * Posts a cancellation fee decision to the ledger (Plan P3.5, SRS §8.4, §12.3). Called by BookingTransitionService inside
 * the transition's transaction with the booking locked, so the decision on the booking and its money never diverge.
 *
 * - The customer owes the fee: their wallet is debited, which makes it a wallet DEBT whatever the payment method (a cash
 *   fee cannot be collected at a pickup that did not happen, and a card is not charged off-session); the provider share
 *   and the platform share are credited. The customer wallet may go negative here on purpose, and
 *   `customer_fee_debt_limit_minor` blocks new bookings until the debt is settled.
 * - The provider owes a fee (or a no-show penalty): their wallet is debited and the platform revenue credited.
 * Every entry balances by construction (one debit, one or two credits of the same sum) and is idempotent by
 * `cancellation-fee:<booking uuid>:<event sequence>:<part>`; the ledger refuses a replay with other lines.
 */
final class CancellationFeePostingService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly BookingRepositoryInterface $bookings,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * @param int|null $providerId the provider of the booking at the time of the decision (a re-dispatch clears it afterwards)
     * @return list<int> ids of the journal entries written (or found) for the decision
     */
    public function post(Booking $booking, CancellationDecision $decision, ?int $providerId, int $sequence): array
    {
        $currency = $this->currencies->get($decision->currency);
        $provider = $providerId === null ? null : $this->providers->find($providerId);
        $entries = [];

        if ($decision->feeMinor > 0) {
            $fee = Money::of($decision->feeMinor, $currency);
            $entries[] = match ($decision->payer()) {
                ActorType::Provider => $this->providerPays($booking, $decision, $provider, $fee, $sequence, 'fee'),
                default => $this->customerPays($booking, $decision, $provider, $fee, $sequence),
            };
        }

        if ($decision->penaltyMinor() > 0) {
            $entries[] = $this->providerPays($booking, $decision, $provider, Money::of($decision->penaltyMinor(), $currency), $sequence, 'penalty');
        }

        if ($entries !== [] && $booking->status->isCancellation()) {
            $this->bookings->forceUpdate($booking, ['cancellation_fee_entry_id' => $entries[0], 'cancellation_fee_posted_at' => CarbonImmutable::now()]);
        }

        return $entries;
    }

    private function customerPays(Booking $booking, CancellationDecision $decision, ?ProviderProfile $provider, Money $fee, int $sequence): int
    {
        $booking->loadMissing('customer');
        $share = $decision->recipient === CancellationFeeRecipient::Provider && $provider instanceof ProviderProfile
            ? $fee->multiplyBasisPoints($decision->providerShareBp ?? 10_000)
            : Money::zero($fee->currency);
        $platform = $fee->subtract($share);
        $lines = [JournalLineData::debit($this->accounts->customerWallet($booking->customer, $fee->currency->code), $fee)];

        if ($share->isPositive() && $provider instanceof ProviderProfile) {
            $lines[] = JournalLineData::credit($this->accounts->providerWallet($provider, $fee->currency->code), $share);
        }

        if ($platform->isPositive()) {
            $lines[] = JournalLineData::credit($this->accounts->platform(LedgerAccountType::PlatformRevenue, $fee->currency->code), $platform);
        }

        return $this->write($booking, $decision, $sequence, 'fee', $lines, ['payer' => ActorType::Customer->value, 'provider_share_minor' => $share->amount, 'platform_share_minor' => $platform->amount]);
    }

    private function providerPays(Booking $booking, CancellationDecision $decision, ?ProviderProfile $provider, Money $amount, int $sequence, string $part): int
    {
        if (! $provider instanceof ProviderProfile) {
            throw new LogicException('A fee owed by the provider needs the provider of the booking.');
        }

        $lines = [
            JournalLineData::debit($this->accounts->providerWallet($provider, $amount->currency->code), $amount),
            JournalLineData::credit($this->accounts->platform(LedgerAccountType::PlatformRevenue, $amount->currency->code), $amount),
        ];

        return $this->write($booking, $decision, $sequence, $part, $lines, ['payer' => ActorType::Provider->value, 'platform_share_minor' => $amount->amount]);
    }

    /**
     * @param list<JournalLineData> $lines
     * @param array<string, mixed> $meta
     */
    private function write(Booking $booking, CancellationDecision $decision, int $sequence, string $part, array $lines, array $meta): int
    {
        $result = $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::CancellationFee,
            idempotencyKey: 'cancellation-fee:'.$booking->uuid.':'.$sequence.':'.$part,
            descriptionKey: 'ledger.entry.cancellation_fee',
            lines: $lines,
            referenceType: 'booking',
            referenceId: $booking->id,
            meta: $meta + ['booking' => $booking->code, 'stage' => $decision->stage->value, 'basis' => $decision->basis, 'part' => $part, 'actor' => $decision->actor->value],
            actorType: ActorType::System,
            enforceNegativePolicy: false,
        ));

        return $result->entry->id;
    }
}
