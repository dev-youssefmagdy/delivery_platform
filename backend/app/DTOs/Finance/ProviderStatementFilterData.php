<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The provider statement export: which provider (internal id, set by the controller) and the posted date range.
 */
final readonly class ProviderStatementFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(
        public ?int $providerId = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'posted_at');

        return new self(self::intFilter($input, 'provider_id'), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'provider_id' => $this->providerId,
            'posted_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
