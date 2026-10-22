<?php

namespace App\DTOs\Notification;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Notification\CampaignAudience;
use App\Enums\Notification\CampaignStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Push campaigns listing: name, status, audience and the scheduled/created date range.
 */
final readonly class PushCampaignFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<CampaignStatus> $statuses
     * @param list<CampaignAudience> $audiences
     */
    public function __construct(
        public ?string $name = null,
        public array $statuses = [],
        public array $audiences = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');

        return new self(
            name: self::stringFilter($input, 'name', 120),
            statuses: self::enumListFilter($input, 'statuses', CampaignStatus::class),
            audiences: self::enumListFilter($input, 'audiences', CampaignAudience::class),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->name, $this->statuses, $this->audiences, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'statuses' => array_map(static fn (CampaignStatus $case): string => $case->value, $this->statuses),
            'audiences' => array_map(static fn (CampaignAudience $case): string => $case->value, $this->audiences),
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
