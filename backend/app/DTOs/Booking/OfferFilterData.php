<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Booking\OfferAnomaly;
use App\Enums\Booking\OfferKind;
use App\Enums\Booking\OfferStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Offers monitor (Plan Appendix C): booking code, provider, status, kind, amount range (major units in
 * the form, minor units here), anomaly flag and created range.
 */
final readonly class OfferFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $providerIds
     * @param list<OfferStatus> $statuses
     * @param list<OfferKind> $kinds
     */
    public function __construct(
        public ?string $bookingCode = null,
        public array $providerIds = [],
        public array $statuses = [],
        public array $kinds = [],
        public ?int $amountMinMinor = null,
        public ?int $amountMaxMinor = null,
        public ?OfferAnomaly $anomaly = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');
        $amount = is_array($input['amount'] ?? null) ? $input['amount'] : [];
        $anomaly = self::stringFilter($input, 'anomaly', 16);

        return new self(
            bookingCode: self::stringFilter($input, 'booking_code', 16),
            providerIds: self::idListFilter($input, 'provider_ids'),
            statuses: self::enumListFilter($input, 'statuses', OfferStatus::class),
            kinds: self::enumListFilter($input, 'kinds', OfferKind::class),
            amountMinMinor: self::minorUnitsFilter($amount, 'min'),
            amountMaxMinor: self::minorUnitsFilter($amount, 'max'),
            anomaly: $anomaly === null ? null : OfferAnomaly::tryFrom($anomaly),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->bookingCode, $this->providerIds, $this->statuses, $this->kinds, $this->amountMinMinor ?? $this->amountMaxMinor,
            $this->anomaly, $this->from ?? $this->to,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'booking_code' => $this->bookingCode,
            'provider_ids' => $this->providerIds,
            'statuses' => array_map(static fn (OfferStatus $status): string => $status->value, $this->statuses),
            'kinds' => array_map(static fn (OfferKind $kind): string => $kind->value, $this->kinds),
            'amount' => array_filter([
                'min' => $this->amountMinMinor === null ? null : self::decimalFromMinor($this->amountMinMinor),
                'max' => $this->amountMaxMinor === null ? null : self::decimalFromMinor($this->amountMaxMinor),
            ]),
            'anomaly' => $this->anomaly?->value,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
