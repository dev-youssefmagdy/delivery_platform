<?php

namespace App\Repositories\Eloquent\Payments;

use App\DTOs\Payments\WebhookEventFilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\WebhookOutcome;
use App\Models\GatewayWebhookEvent;
use App\Repositories\Contracts\Payments\GatewayWebhookEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class GatewayWebhookEventRepository implements GatewayWebhookEventRepositoryInterface
{
    public function find(int $id): ?GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->find($id);
    }

    public function findByUuid(string $uuid): ?GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->where('uuid', $uuid)->first();
    }

    public function findByEventId(PaymentGatewayName $gateway, string $eventId): ?GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->where('gateway', $gateway->value)->where('event_id', $eventId)->first();
    }

    public function lockByEventId(PaymentGatewayName $gateway, string $eventId): ?GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->where('gateway', $gateway->value)->where('event_id', $eventId)->lockForUpdate()->first();
    }

    public function lockById(int $id): ?GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function insert(array $attributes): GatewayWebhookEvent
    {
        return GatewayWebhookEvent::query()->create($attributes);
    }

    public function update(GatewayWebhookEvent $event, array $attributes): GatewayWebhookEvent
    {
        $event->forceFill($attributes)->save();

        return $event;
    }

    public function needingAttentionCount(): int
    {
        return GatewayWebhookEvent::query()->whereIn('outcome', self::attentionOutcomes())->whereNull('processed_at')->count();
    }

    public function datatableQuery(WebhookEventFilterData $filters): Builder
    {
        $values = static fn (array $cases): array => array_map(static fn ($case): string => $case->value, $cases);

        return GatewayWebhookEvent::query()
            ->select('gateway_webhook_events.*')
            ->with('payment:id,uuid')
            ->when($filters->outcomes !== [], static fn (Builder $query) => $query->whereIn('outcome', $values($filters->outcomes)))
            ->when($filters->gateways !== [], static fn (Builder $query) => $query->whereIn('gateway', $values($filters->gateways)))
            ->when($filters->kinds !== [], static fn (Builder $query) => $query->whereIn('kind', $values($filters->kinds)))
            ->when($filters->signatureValid !== null, static fn (Builder $query) => $query->where('signature_valid', $filters->signatureValid))
            ->when($filters->needsAttention === true, static fn (Builder $query) => $query->whereIn('outcome', self::attentionOutcomes())->whereNull('processed_at'))
            ->when($filters->search !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner
                ->where('event_id', $filters->search)
                ->orWhere('gateway_reference', $filters->search)
                ->orWhere('uuid', $filters->search)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('received_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('received_at', '<=', $filters->to?->endOfDay()));
    }

    /**
     * @return list<string>
     */
    private static function attentionOutcomes(): array
    {
        return [WebhookOutcome::Failed->value, WebhookOutcome::Unmatched->value, WebhookOutcome::AmountMismatch->value];
    }
}
