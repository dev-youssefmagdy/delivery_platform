<?php

namespace App\DTOs\Growth;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the referral relationships listing: campaign, audience, status, flagged only, created range.
 */
final readonly class RelationshipFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $campaignIds
     * @param list<ReferralAudience> $audiences
     * @param list<RelationshipStatus> $statuses
     */
    public function __construct(public array $campaignIds = [], public array $audiences = [], public array $statuses = [], public ?bool $flagged = null, public ?CarbonImmutable $from = null, public ?CarbonImmutable $to = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');

        return new self(self::idListFilter($input, 'campaign_ids'), self::enumListFilter($input, 'audiences', ReferralAudience::class), self::enumListFilter($input, 'statuses', RelationshipStatus::class), self::boolFilter($input, 'flagged'), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->campaignIds, $this->audiences, $this->statuses, $this->flagged, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'campaign_ids' => $this->campaignIds,
            'audiences' => array_map(static fn (ReferralAudience $case): string => $case->value, $this->audiences),
            'statuses' => array_map(static fn (RelationshipStatus $case): string => $case->value, $this->statuses),
            'flagged' => $this->flagged,
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== [] && $value !== null);
    }
}
