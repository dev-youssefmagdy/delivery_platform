<?php

namespace App\DTOs\Payments;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\WebhookEventKind;
use App\Enums\Payments\WebhookOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Gateway webhook events listing: outcome (failures first-class), gateway, kind, signature validity,
 * event id / gateway reference and the received range.
 */
final readonly class WebhookEventFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<WebhookOutcome> $outcomes
     * @param list<PaymentGatewayName> $gateways
     * @param list<WebhookEventKind> $kinds
     */
    public function __construct(
        public array $outcomes = [],
        public array $gateways = [],
        public array $kinds = [],
        public ?bool $signatureValid = null,
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?bool $needsAttention = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'received_at');

        return new self(
            outcomes: self::enumListFilter($input, 'outcomes', WebhookOutcome::class),
            gateways: self::enumListFilter($input, 'gateways', PaymentGatewayName::class),
            kinds: self::enumListFilter($input, 'kinds', WebhookEventKind::class),
            signatureValid: self::boolFilter($input, 'signature_valid'),
            search: self::stringFilter($input, 'search', 160),
            from: $from,
            to: $to,
            needsAttention: self::boolFilter($input, 'needs_attention'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->outcomes, $this->gateways, $this->kinds, $this->signatureValid, $this->search, $this->from ?? $this->to, $this->needsAttention]);
    }

    public function toArray(): array
    {
        return array_filter([
            'outcomes' => array_map(static fn (WebhookOutcome $outcome): string => $outcome->value, $this->outcomes),
            'gateways' => array_map(static fn (PaymentGatewayName $gateway): string => $gateway->value, $this->gateways),
            'kinds' => array_map(static fn (WebhookEventKind $kind): string => $kind->value, $this->kinds),
            'signature_valid' => $this->signatureValid === null ? null : ($this->signatureValid ? '1' : '0'),
            'search' => $this->search,
            'received_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'needs_attention' => $this->needsAttention === null ? null : ($this->needsAttention ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
