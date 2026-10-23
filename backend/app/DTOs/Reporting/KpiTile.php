<?php

namespace App\DTOs\Reporting;

use App\Enums\Reporting\DataFreshness;
use App\Enums\Reporting\KpiKey;
use Carbon\CarbonInterface;

/**
 * One KPI widget: the formatted value, an optional comparison with the previous period, the drill-down link and the definition shown by
 * the info tooltip. No personal data: a tile is a count, a rate or a money sum.
 */
final readonly class KpiTile
{
    /**
     * @param array{numerator: string, denominator: string, timezone: string} $definition
     */
    public function __construct(
        public KpiKey $key,
        public string $value,
        public ?string $hint,
        public ?string $href,
        public array $definition,
        public DataFreshness $freshness,
        public ?CarbonInterface $asOf = null,
        public ?string $change = null,
        public ?string $changeDirection = null,
    ) {}
}
