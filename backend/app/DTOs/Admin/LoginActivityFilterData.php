<?php

namespace App\DTOs\Admin;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Admin\AdminLoginOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the login activity listing (email, outcome, IP, date range).
 */
final readonly class LoginActivityFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<AdminLoginOutcome> $outcomes
     */
    public function __construct(
        public ?string $email = null,
        public array $outcomes = [],
        public ?string $ip = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            email: self::stringFilter($input, 'email'),
            outcomes: self::enumListFilter($input, 'outcomes', AdminLoginOutcome::class),
            ip: self::stringFilter($input, 'ip', 45),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->email, $this->outcomes, $this->ip, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'outcomes' => array_map(static fn (AdminLoginOutcome $outcome): string => $outcome->value, $this->outcomes),
            'ip' => $this->ip,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
