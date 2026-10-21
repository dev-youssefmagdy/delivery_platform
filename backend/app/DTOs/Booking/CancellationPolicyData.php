<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CancellationFeeType;
use App\Http\Requests\Admin\Booking\StoreCancellationPolicyRequest;
use App\Http\Requests\Admin\Booking\UpdateCancellationPolicyRequest;
use App\Models\CancellationPolicy;
use Carbon\CarbonImmutable;

/**
 * Validated input of creating / updating a cancellation policy. Fixed fees and caps are typed in major units and
 * converted with the currency exponent; percentages (two decimals) become basis points. Business checks (overlap,
 * rule sanity) are made again by `CancellationPolicyService` under lock.
 */
final readonly class CancellationPolicyData
{
    /**
     * @param list<array{actor: string, stage: string, fee_type: string, value: int, cap_minor: int|null, recipient: string}> $rules
     * @param list<string> $customerReasons
     * @param list<string> $providerReasons
     */
    public function __construct(
        public string $name,
        public ?int $serviceCategoryId,
        public ?int $serviceZoneId,
        public int $freeGraceSeconds,
        public int $freeWaitSeconds,
        public array $rules,
        public array $customerReasons,
        public array $providerReasons,
        public CarbonImmutable $effectiveFrom,
        public ?CarbonImmutable $effectiveTo,
        public ?string $notes,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCancellationPolicyRequest|UpdateCancellationPolicyRequest $request, int $exponent): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('cancellationPolicy');
        $timezone = (string) config('admin.display_timezone', 'UTC');
        $rules = [];

        foreach ((array) ($data['rules'] ?? []) as $row) {
            $row = (array) $row;
            $type = (string) ($row['fee_type'] ?? CancellationFeeType::None->value);

            if (trim((string) ($row['actor'] ?? '')) === '' && trim((string) ($row['stage'] ?? '')) === '') {
                continue;
            }

            $value = match ($type) {
                CancellationFeeType::Fixed->value => self::toMinor(trim((string) ($row['amount'] ?? '')), $exponent),
                CancellationFeeType::Percent->value => self::toBasisPoints(trim((string) ($row['percent'] ?? ''))),
                default => 0,
            };
            $cap = trim((string) ($row['cap'] ?? ''));

            $rules[] = [
                'actor' => (string) ($row['actor'] ?? ''),
                'stage' => (string) ($row['stage'] ?? ''),
                'fee_type' => $type,
                'value' => $value,
                'cap_minor' => $cap === '' || $type !== CancellationFeeType::Percent->value ? null : self::toMinor($cap, $exponent),
                'recipient' => (string) ($row['recipient'] ?? 'platform'),
            ];
        }

        return new self(
            name: trim((string) $data['name']),
            serviceCategoryId: $current instanceof CancellationPolicy
                ? $current->service_category_id
                : (isset($data['service_category_id']) && $data['service_category_id'] !== '' ? (int) $data['service_category_id'] : null),
            serviceZoneId: $current instanceof CancellationPolicy
                ? $current->service_zone_id
                : (isset($data['service_zone_id']) && $data['service_zone_id'] !== '' ? (int) $data['service_zone_id'] : null),
            freeGraceSeconds: (int) $data['free_grace_seconds'],
            freeWaitSeconds: (int) $data['free_wait_seconds'],
            rules: $rules,
            customerReasons: array_values(array_map('strval', (array) ($data['customer_reasons'] ?? []))),
            providerReasons: array_values(array_map('strval', (array) ($data['provider_reasons'] ?? []))),
            effectiveFrom: CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_from'], $timezone)->utc(),
            effectiveTo: isset($data['effective_to']) && $data['effective_to'] !== '' ? CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_to'], $timezone)->utc() : null,
            notes: isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'free_grace_seconds' => $this->freeGraceSeconds,
            'free_wait_seconds' => $this->freeWaitSeconds,
            'rules' => $this->rules,
            'customer_reasons' => $this->customerReasons,
            'provider_reasons' => $this->providerReasons,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
            'notes' => $this->notes,
        ];
    }

    /**
     * "12.50" in major units to integer minor units. Invalid input becomes -1 (the service rejects it).
     */
    private static function toMinor(string $amount, int $exponent): int
    {
        if (preg_match('/^\d{1,12}(\.\d{1,'.max($exponent, 1).'})?$/', $amount) !== 1) {
            return -1;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * (10 ** $exponent) + (int) str_pad(substr($fraction, 0, $exponent), $exponent, '0');
    }

    /**
     * "12.5" percent to 1250 basis points (two decimals at most). Invalid input becomes -1.
     */
    private static function toBasisPoints(string $percent): int
    {
        if (preg_match('/^\d{1,3}(\.\d{1,2})?$/', $percent) !== 1) {
            return -1;
        }

        [$whole, $fraction] = array_pad(explode('.', $percent, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
