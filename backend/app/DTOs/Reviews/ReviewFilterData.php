<?php

namespace App\DTOs\Reviews;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\ModerationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Reviews listing (Plan Appendix C "Reviews"): stars range, moderation status, reported only, ratee type, date range,
 * plus the booking code.
 */
final readonly class ReviewFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ModerationStatus> $statuses
     */
    public function __construct(
        public ?int $starsMin = null,
        public ?int $starsMax = null,
        public array $statuses = [],
        public ?bool $reportedOnly = null,
        public ?ActorType $rateeType = null,
        public ?string $bookingCode = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');
        [$min, $max] = self::intRangeFilter($input, 'stars');
        $ratee = self::stringFilter($input, 'ratee_type', 16);

        return new self(
            starsMin: $min === null ? null : max(1, min(5, $min)),
            starsMax: $max === null ? null : max(1, min(5, $max)),
            statuses: self::enumListFilter($input, 'statuses', ModerationStatus::class),
            reportedOnly: self::boolFilter($input, 'reported_only'),
            rateeType: in_array($ratee, [ActorType::Customer->value, ActorType::Provider->value], true) ? ActorType::from((string) $ratee) : null,
            bookingCode: self::stringFilter($input, 'booking', 40),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->starsMin ?? $this->starsMax, $this->statuses, $this->reportedOnly, $this->rateeType, $this->bookingCode, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'stars' => array_filter(['min' => $this->starsMin, 'max' => $this->starsMax]),
            'statuses' => array_map(static fn (ModerationStatus $case): string => $case->value, $this->statuses),
            'reported_only' => $this->reportedOnly === null ? null : ($this->reportedOnly ? '1' : '0'),
            'ratee_type' => $this->rateeType?->value,
            'booking' => $this->bookingCode,
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
