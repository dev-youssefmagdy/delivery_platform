<?php

namespace App\DTOs\Growth;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Enums\Growth\RewardRecipient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the incentive progress listing: campaign (fixed by the page), status.
 */
final readonly class ProgressFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<IncentiveProgressStatus> $statuses
     */
    public function __construct(public ?int $campaignId = null, public array $statuses = []) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        return new self(self::intFilter($input, 'campaign_id'), self::enumListFilter($input, 'statuses', IncentiveProgressStatus::class));
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses]);
    }

    public function toArray(): array
    {
        return array_filter([
            'campaign_id' => $this->campaignId,
            'statuses' => array_map(static fn (IncentiveProgressStatus $case): string => $case->value, $this->statuses),
        ], static fn (mixed $value): bool => $value !== [] && $value !== null);
    }
}
