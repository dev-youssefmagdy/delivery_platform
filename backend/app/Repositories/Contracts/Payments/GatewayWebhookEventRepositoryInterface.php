<?php

namespace App\Repositories\Contracts\Payments;

use App\DTOs\Payments\WebhookEventFilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Models\GatewayWebhookEvent;
use Illuminate\Database\Eloquent\Builder;

interface GatewayWebhookEventRepositoryInterface
{
    public function find(int $id): ?GatewayWebhookEvent;

    public function findByUuid(string $uuid): ?GatewayWebhookEvent;

    public function findByEventId(PaymentGatewayName $gateway, string $eventId): ?GatewayWebhookEvent;

    /**
     * Row lock of an event so two deliveries of it cannot be processed at the same time. Inside a transaction.
     */
    public function lockByEventId(PaymentGatewayName $gateway, string $eventId): ?GatewayWebhookEvent;

    public function lockById(int $id): ?GatewayWebhookEvent;

    /**
     * Inserts the event; the unique (gateway, event_id) key raises UniqueConstraintViolationException when it exists.
     *
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): GatewayWebhookEvent;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(GatewayWebhookEvent $event, array $attributes): GatewayWebhookEvent;

    /**
     * Events that need an operator: failed, unmatched or amount mismatch and not processed.
     */
    public function needingAttentionCount(): int;

    /**
     * @return Builder<GatewayWebhookEvent>
     */
    public function datatableQuery(WebhookEventFilterData $filters): Builder;
}
