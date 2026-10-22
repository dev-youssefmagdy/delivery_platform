<?php

namespace App\DTOs\Notification;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use Illuminate\Http\Request;

/**
 * Filters of the Notification templates listing: event key, category, channels, locale and the active flag.
 */
final readonly class NotificationTemplateFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<NotificationChannel> $channels
     * @param list<NotificationCategory> $categories
     * @param list<Locale> $locales
     */
    public function __construct(
        public ?string $eventKey = null,
        public array $categories = [],
        public array $channels = [],
        public array $locales = [],
        public ?bool $active = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            eventKey: self::stringFilter($input, 'event_key', 64),
            categories: self::enumListFilter($input, 'categories', NotificationCategory::class),
            channels: self::enumListFilter($input, 'channels', NotificationChannel::class),
            locales: self::enumListFilter($input, 'locales', Locale::class),
            active: self::boolFilter($input, 'active'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->eventKey, $this->categories, $this->channels, $this->locales, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'event_key' => $this->eventKey,
            'categories' => array_map(static fn (NotificationCategory $case): string => $case->value, $this->categories),
            'channels' => array_map(static fn (NotificationChannel $case): string => $case->value, $this->channels),
            'locales' => array_map(static fn (Locale $case): string => $case->value, $this->locales),
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
