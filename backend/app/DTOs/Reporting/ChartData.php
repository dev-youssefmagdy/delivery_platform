<?php

namespace App\DTOs\Reporting;

use App\Enums\Reporting\KpiUnit;

/**
 * What a chart component draws: a type (line, bar, donut), labels and series. The series carry a colour SLOT (1..8, the validated
 * categorical order, assigned by series identity and never by rank) and the unit of the values; money is sent in minor units with its
 * currency and exponent and formatted in the browser with the viewer's locale. `labelKind` `date` labels are Y-m-d.
 */
final readonly class ChartData
{
    /**
     * @param list<string> $labels
     * @param list<array{key: string, label: string, data: list<int|float|null>, slot: int}> $series
     */
    public function __construct(
        public string $type,
        public array $labels,
        public array $series,
        public KpiUnit $unit = KpiUnit::Count,
        public ?string $currency = null,
        public string $labelKind = 'text',
        public ?string $summary = null,
    ) {}

    public static function empty(string $type = 'bar'): self
    {
        return new self($type, [], []);
    }

    public function isEmpty(): bool
    {
        if ($this->labels === [] || $this->series === []) {
            return true;
        }

        foreach ($this->series as $series) {
            foreach ($series['data'] as $value) {
                if ($value !== null && (float) $value !== 0.0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'labels' => $this->labels,
            'series' => $this->series,
            'unit' => $this->unit->value,
            'currency' => $this->currency,
            'exponent' => 2,
            'labelKind' => $this->labelKind,
            'summary' => $this->summary,
            'empty' => $this->isEmpty(),
        ];
    }
}
