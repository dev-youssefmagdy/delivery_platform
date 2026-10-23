<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Models\BookingOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * The provider's own offer: its status in the driver's words (pending / selected / rejected / expired / invalidated /
 * withdrawn), the current version and, when the submission just happened, the fare hints and earnings preview.
 *
 * @property BookingOffer $resource
 */
class ProviderOfferResource extends JsonResource
{
    /**
     * @param list<string> $fareHints
     * @param array<string, mixed>|null $earningsPreview
     */
    public function __construct(BookingOffer $offer, private readonly array $fareHints = [], private readonly ?array $earningsPreview = null, private readonly bool $replayed = false)
    {
        parent::__construct($offer);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $offer = $this->resource;
        $version = $offer->currentVersion;

        return [
            'id' => $offer->uuid,
            'booking_id' => $offer->booking->uuid,
            'status' => $offer->status->value,
            'state' => $offer->status->providerView(),
            'kind' => $offer->kind->value,
            'amount' => $version === null ? null : ['amount_minor' => $version->amount_minor, 'currency' => $version->currency],
            'version' => $version?->version,
            'revision_count' => $offer->revision_count,
            'note' => $version?->note,
            'scope' => ($version->scope ?? []) === [] ? new stdClass : $version?->scope,
            'scope_summary' => app(\App\Services\Scope\OfferScopeService::class)->summary($version?->scope),
            'eta_seconds' => $offer->eta_seconds,
            'distance_m' => $offer->distance_m,
            'expires_at' => $offer->expires_at->toIso8601ZuluString(),
            'closed_at' => $offer->closed_at?->toIso8601ZuluString(),
            'fare_hints' => $this->fareHints,
            'earnings_preview' => $this->earningsPreview,
            'replayed' => $this->replayed,
        ];
    }
}
