<?php

namespace App\DTOs\Notification;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Notification\DeliveryStatus;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Delivery log (Plan Appendix C): channel, status, event key and date range.
 */
final readonly class NotificationDeliveryFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<NotificationChannel> $channels
     * @param list<DeliveryStatus> $statuses
     * @param list<NotificationEvent> $events
     */
    public function __construct(
        public array $channels = [],
        public array $statuses = [],
        public array $events = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');

        return new self(
            channels: self::enumListFilter($input, 'channels', NotificationChannel::class),
            statuses: self::enumListFilter($input, 'statuses', DeliveryStatus::class),
            events: self::enumListFilter($input, 'events', NotificationEvent::class),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->channels, $this->statuses, $this->events, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'channels' => array_map(static fn (NotificationChannel $case): string => $case->value, $this->channels),
            'statuses' => array_map(static fn (DeliveryStatus $case): string => $case->value, $this->statuses),
            'events' => array_map(static fn (NotificationEvent $case): string => $case->value, $this->events),
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
