<?php

namespace App\DTOs\Growth;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\RewardRecipient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the referral rewards listing: campaign, recipient, status, granted range.
 */
final readonly class RewardFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $campaignIds
     * @param list<RewardRecipient> $recipients
     * @param list<CreditStatus> $statuses
     */
    public function __construct(public array $campaignIds = [], public array $recipients = [], public array $statuses = [], public ?CarbonImmutable $from = null, public ?CarbonImmutable $to = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'granted');

        return new self(self::idListFilter($input, 'campaign_ids'), self::enumListFilter($input, 'recipients', RewardRecipient::class), self::enumListFilter($input, 'statuses', CreditStatus::class), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->campaignIds, $this->recipients, $this->statuses, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'campaign_ids' => $this->campaignIds,
            'recipients' => array_map(static fn (RewardRecipient $case): string => $case->value, $this->recipients),
            'statuses' => array_map(static fn (CreditStatus $case): string => $case->value, $this->statuses),
            'granted' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== []);
    }
}
