<?php

namespace App\DTOs\Risk;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Risk\RiskSignalStatus;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the risk signals queue: type, status, subject type, minimum score, date range. The default view (no status chosen) is the
 * active queue (open and escalated).
 */
final readonly class RiskSignalFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<RiskSignalType> $types
     * @param list<RiskSignalStatus> $statuses
     */
    public function __construct(
        public array $types = [],
        public array $statuses = [],
        public ?RiskSubjectType $subjectType = null,
        public ?int $minScore = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'detected');
        $min = self::intFilter($input, 'min_score');

        return new self(
            types: self::enumListFilter($input, 'types', RiskSignalType::class),
            statuses: self::enumListFilter($input, 'statuses', RiskSignalStatus::class),
            subjectType: RiskSubjectType::tryFromValue(self::stringFilter($input, 'subject_type', 16)),
            minScore: $min === null ? null : max(0, min(100, $min)),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->types, $this->statuses, $this->subjectType, $this->minScore, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'types' => array_map(static fn (RiskSignalType $case): string => $case->value, $this->types),
            'statuses' => array_map(static fn (RiskSignalStatus $case): string => $case->value, $this->statuses),
            'subject_type' => $this->subjectType?->value,
            'min_score' => $this->minScore,
            'detected' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
