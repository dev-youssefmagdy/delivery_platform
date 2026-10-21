<?php

namespace App\Http\Resources\Api\V1\Trip;

use App\DTOs\Booking\CancellationDecision;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The fee decision shown before the customer confirms (preview) and after the cancellation: amounts in minor units, a
 * stage label and a sentence the app can show as is ("free" with the reason, or the amount that will be charged).
 *
 * @property CancellationDecision $resource
 */
class CancellationDecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $decision = $this->resource;
        $money = Money::of($decision->feeMinor, app(CurrencyRegistry::class)->get($decision->currency));

        return $decision->toArray() + [
            'stage_label' => $decision->stage->label(),
            'fee_formatted' => app(MoneyFormatter::class)->format($money),
            'message' => $decision->isFree()
                ? __("api.cancellation.free.{$decision->basis}")
                : __('api.cancellation.fee_notice', ['amount' => app(MoneyFormatter::class)->format($money)]),
        ];
    }
}
