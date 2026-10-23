<?php

namespace App\DTOs\Growth;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\ReferralAudience;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the referral and incentive campaign listings: audience (referrals only), status, starts range.
 */
final readonly class CampaignFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ReferralAudience> $audiences
     * @param list<CampaignStatus> $statuses
     */
    public function __construct(public array $audiences = [], public array $statuses = [], public ?CarbonImmutable $from = null, public ?CarbonImmutable $to = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'starts');

        return new self(self::enumListFilter($input, 'audiences', ReferralAudience::class), self::enumListFilter($input, 'statuses', CampaignStatus::class), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->audiences, $this->statuses, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'audiences' => array_map(static fn (ReferralAudience $case): string => $case->value, $this->audiences),
            'statuses' => array_map(static fn (CampaignStatus $case): string => $case->value, $this->statuses),
            'starts' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== []);
    }
}
