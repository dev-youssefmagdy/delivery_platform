<?php

namespace App\Repositories\Eloquent\System;

use App\Enums\Payments\WebhookOutcome;
use App\Models\GatewayWebhookEvent;
use App\Repositories\Contracts\System\WebhookFailureRepositoryInterface;
use DateTimeInterface;

final class WebhookFailureRepository implements WebhookFailureRepositoryInterface
{
    private const FAILED = [WebhookOutcome::InvalidSignature, WebhookOutcome::ReplayWindow, WebhookOutcome::AmountMismatch, WebhookOutcome::Failed];

    public function summarySince(DateTimeInterface $since): array
    {
        $values = array_map(static fn (WebhookOutcome $o): string => $o->value, self::FAILED);
        $query = GatewayWebhookEvent::query()->whereIn('outcome', $values)->where('received_at', '>=', $since);
        $last = (clone $query)->orderByDesc('received_at')->first(['gateway', 'outcome', 'received_at']);

        return [
            'count' => $query->count(),
            'last_at' => $last?->received_at,
            'last_gateway' => $last?->gateway->value,
            'last_outcome' => $last?->outcome->value,
        ];
    }
}
