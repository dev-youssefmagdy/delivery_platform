<?php

namespace App\DTOs\Reporting;

use Carbon\CarbonImmutable;

/**
 * A closed range of MARKET days (Y-m-d, the day in each city's own timezone, as stored in `metric_date`) plus the timezone filter:
 * `timezone` null = every market; a timezone name restricts the booking and finance metrics to the cities in that timezone and picks
 * that timezone's operations rows.
 */
final readonly class ReportingRange
{
    public const MAX_DAYS = 366;

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?string $timezone = null,
    ) {}

    public static function lastDays(int $days, ?string $timezone = null, ?string $today = null): self
    {
        $to = CarbonImmutable::parse($today ?? now((string) config('admin.display_timezone', 'UTC'))->toDateString())->startOfDay();

        return new self($to->subDays(max(0, $days - 1)), $to, $timezone);
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /**
     * The same number of days directly before this range.
     */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->from->subDays($days), $this->from->subDay(), $this->timezone);
    }

    /**
     * @return list<string> every Y-m-d of the range
     */
    public function dates(): array
    {
        $dates = [];

        for ($day = $this->from; $day->lte($this->to); $day = $day->addDay()) {
            $dates[] = $day->toDateString();
        }

        return $dates;
    }

    public function isValid(): bool
    {
        return $this->from->lte($this->to) && $this->days() <= self::MAX_DAYS;
    }
}
