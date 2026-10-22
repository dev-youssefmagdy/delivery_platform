<?php

namespace App\DTOs\Payments;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Payments listing: status, kind, method, gateway, booking code or payment/gateway reference, created range
 * and amount range (minor units).
 */
final readonly class PaymentFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<PaymentStatus> $statuses
     * @param list<PaymentKind> $kinds
     * @param list<PaymentMethodType> $methods
     * @param list<PaymentGatewayName> $gateways
     */
    public function __construct(
        public array $statuses = [],
        public array $kinds = [],
        public array $methods = [],
        public array $gateways = [],
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $minAmountMinor = null,
        public ?int $maxAmountMinor = null,
        public ?int $bookingId = null,
        public ?int $userId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');
        $range = is_array($input['amount'] ?? null) ? $input['amount'] : [];
        [$min, $max] = [self::minorUnitsFilter($range, 'min'), self::minorUnitsFilter($range, 'max')];

        return new self(
            statuses: self::enumListFilter($input, 'statuses', PaymentStatus::class),
            kinds: self::enumListFilter($input, 'kinds', PaymentKind::class),
            methods: self::enumListFilter($input, 'methods', PaymentMethodType::class),
            gateways: self::enumListFilter($input, 'gateways', PaymentGatewayName::class),
            search: self::stringFilter($input, 'search', 120),
            from: $from,
            to: $to,
            minAmountMinor: $min,
            maxAmountMinor: $max,
        );
    }

    public function forBooking(int $bookingId): self
    {
        return new self(bookingId: $bookingId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->kinds, $this->methods, $this->gateways, $this->search, $this->from ?? $this->to, $this->minAmountMinor ?? $this->maxAmountMinor]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (PaymentStatus $status): string => $status->value, $this->statuses),
            'kinds' => array_map(static fn (PaymentKind $kind): string => $kind->value, $this->kinds),
            'methods' => array_map(static fn (PaymentMethodType $method): string => $method->value, $this->methods),
            'gateways' => array_map(static fn (PaymentGatewayName $gateway): string => $gateway->value, $this->gateways),
            'search' => $this->search,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'amount' => array_filter([
                'min' => $this->minAmountMinor === null ? null : self::decimalFromMinor($this->minAmountMinor),
                'max' => $this->maxAmountMinor === null ? null : self::decimalFromMinor($this->maxAmountMinor),
            ], static fn (?string $value): bool => $value !== null),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
