<?php

namespace App\Support\Geo;

use Carbon\CarbonInterface;

/**
 * Weekly opening windows in the same shape as a category's service hours: `[{day: 1..7 (ISO), from: "HH:MM",
 * to: "HH:MM"}]`, with `from < to`. No windows at all means "always open". The evaluated time must already be
 * in the city's timezone.
 */
final readonly class OperatingHours
{
    /**
     * @param list<array{day: int, from: string, to: string}> $windows
     */
    public function __construct(public array $windows = []) {}

    /**
     * @param array<array-key, mixed>|null $raw
     */
    public static function fromArray(?array $raw): self
    {
        $windows = [];

        foreach ($raw ?? [] as $row) {
            if (is_array($row) && isset($row['day'], $row['from'], $row['to'])) {
                $windows[] = ['day' => (int) $row['day'], 'from' => (string) $row['from'], 'to' => (string) $row['to']];
            }
        }

        return new self($windows);
    }

    public function isAlwaysOpen(): bool
    {
        return $this->windows === [];
    }

    public function isOpenAt(CarbonInterface $local): bool
    {
        if ($this->isAlwaysOpen()) {
            return true;
        }

        $day = $local->dayOfWeekIso;
        $time = $local->format('H:i');

        foreach ($this->windows as $window) {
            if ($window['day'] === $day && $window['from'] <= $time && $time < $window['to']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Next opening instant after `$local` within the coming week, for "opens at" messages. Null when always open
     * or never open.
     */
    public function nextOpeningAfter(CarbonInterface $local): ?CarbonInterface
    {
        if ($this->isAlwaysOpen()) {
            return null;
        }

        $best = null;

        foreach ($this->windows as $window) {
            [$hour, $minute] = array_map('intval', explode(':', $window['from']));
            $candidate = $local->copy()->startOfDay()->addDays(($window['day'] - $local->dayOfWeekIso + 7) % 7)->setTime($hour, $minute);

            if ($candidate <= $local) {
                $candidate = $candidate->addWeek();
            }

            if ($best === null || $candidate < $best) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @return list<array{day: int, from: string, to: string}>|null
     */
    public function toStorage(): ?array
    {
        return $this->windows === [] ? null : $this->windows;
    }
}
