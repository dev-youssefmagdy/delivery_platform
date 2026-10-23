<?php

namespace App\DTOs\System;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class FailedJobFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(public ?string $queue = null, public ?CarbonImmutable $from = null, public ?CarbonImmutable $to = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'failed');

        return new self(self::stringFilter($input, 'queue', 64), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->queue, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'queue' => $this->queue,
            'failed' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $v): bool => $v !== null && $v !== []);
    }
}
