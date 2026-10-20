<?php

namespace App\DTOs\Identity;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpChallengeState;
use App\Enums\Identity\OtpDeliveryStatus;
use App\Support\Auth\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the OTP challenges log: phone (full number, matched by keyed hash), state, delivery, app, IP, dates.
 */
final readonly class OtpChallengeFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<OtpChallengeState> $states
     * @param list<OtpDeliveryStatus> $deliveries
     * @param list<ClientApp> $apps
     */
    public function __construct(
        public ?string $phone = null,
        public array $states = [],
        public array $deliveries = [],
        public array $apps = [],
        public ?string $ip = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');
        $phone = self::stringFilter($input, 'phone', 32);

        return new self(
            phone: $phone === null ? null : (PhoneNumber::normalize($phone) ?? $phone),
            states: self::enumListFilter($input, 'states', OtpChallengeState::class),
            deliveries: self::enumListFilter($input, 'deliveries', OtpDeliveryStatus::class),
            apps: self::enumListFilter($input, 'apps', ClientApp::class),
            ip: self::stringFilter($input, 'ip', 45),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->phone, $this->states, $this->deliveries, $this->apps, $this->ip, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'phone' => $this->phone,
            'states' => array_map(static fn (OtpChallengeState $state): string => $state->value, $this->states),
            'deliveries' => array_map(static fn (OtpDeliveryStatus $status): string => $status->value, $this->deliveries),
            'apps' => array_map(static fn (ClientApp $app): string => $app->value, $this->apps),
            'ip' => $this->ip,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
