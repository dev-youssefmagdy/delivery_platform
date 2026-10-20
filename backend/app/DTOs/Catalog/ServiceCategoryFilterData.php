<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Catalog\BookingType;
use Illuminate\Http\Request;

/**
 * Filters of the Service Categories listing (Appendix C: family, active, booking type, negotiation enabled).
 */
final readonly class ServiceCategoryFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(
        public ?string $search = null,
        public ?int $familyId = null,
        public ?bool $active = null,
        public ?BookingType $bookingType = null,
        public ?bool $negotiationEnabled = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            search: self::stringFilter($input, 'search'),
            familyId: self::intFilter($input, 'family'),
            active: self::boolFilter($input, 'active'),
            bookingType: self::enumListFilter($input, 'booking_type', BookingType::class)[0] ?? null,
            negotiationEnabled: self::boolFilter($input, 'negotiation_enabled'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->familyId, $this->active, $this->bookingType, $this->negotiationEnabled]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'family' => $this->familyId,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
            'booking_type' => $this->bookingType?->value,
            'negotiation_enabled' => $this->negotiationEnabled === null ? null : ($this->negotiationEnabled ? '1' : '0'),
        ], static fn (string|int|null $value): bool => $value !== null);
    }
}
