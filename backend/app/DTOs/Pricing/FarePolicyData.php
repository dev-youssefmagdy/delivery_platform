<?php

namespace App\DTOs\Pricing;

use App\Enums\Pricing\FareFormula;
use App\Enums\Pricing\SurchargeMode;
use App\Http\Requests\Admin\Pricing\StoreFarePolicyRequest;
use App\Http\Requests\Admin\Pricing\UpdateFarePolicyRequest;
use App\Models\FarePolicy;
use Carbon\CarbonImmutable;

/**
 * Validated input of creating / updating a fare policy. Amounts are integer minor units; the surcharge amounts typed
 * as decimals in the form are converted with the currency exponent by the request.
 */
final readonly class FarePolicyData
{
    /**
     * @param list<array{field: string, mode: string, amount_minor: int, match: string|null, label: array<string, string|null>}> $surcharges
     */
    public function __construct(
        public int $serviceCategoryId,
        public ?int $serviceZoneId,
        public FareFormula $formula,
        public string $currency,
        public int $baseMinor,
        public int $perKmMinor,
        public int $perMinMinor,
        public int $minFareMinor,
        public int $stopFeeMinor,
        public array $surcharges,
        public int $roundingStepMinor,
        public int $rangeLowBp,
        public int $rangeHighBp,
        public CarbonImmutable $effectiveFrom,
        public ?CarbonImmutable $effectiveTo,
        public ?string $notes,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreFarePolicyRequest|UpdateFarePolicyRequest $request, string $currency, int $exponent): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('farePolicy');
        $timezone = (string) config('admin.display_timezone', 'UTC');

        $surcharges = [];

        foreach ((array) ($data['surcharges'] ?? []) as $row) {
            $row = (array) $row;
            $amount = trim((string) ($row['amount'] ?? ''));

            if (trim((string) ($row['field'] ?? '')) === '' && $amount === '') {
                continue;
            }

            $surcharges[] = [
                'field' => trim((string) ($row['field'] ?? '')),
                'mode' => (string) ($row['mode'] ?? SurchargeMode::Fixed->value),
                'amount_minor' => self::toMinor($amount, $exponent),
                'match' => ($match = trim((string) ($row['match'] ?? ''))) === '' ? null : $match,
                'label' => ['ar' => self::nullable($row['label_ar'] ?? null), 'en' => self::nullable($row['label_en'] ?? null)],
            ];
        }

        return new self(
            serviceCategoryId: (int) ($data['service_category_id'] ?? ($current instanceof FarePolicy ? $current->service_category_id : 0)),
            serviceZoneId: array_key_exists('service_category_id', $data)
                ? (isset($data['service_zone_id']) && $data['service_zone_id'] !== '' ? (int) $data['service_zone_id'] : null)
                : ($current instanceof FarePolicy ? $current->service_zone_id : null),
            formula: FareFormula::from((int) ($data['formula_version'] ?? FareFormula::V1->value)),
            currency: $current instanceof FarePolicy ? $current->currency : $currency,
            baseMinor: (int) ($data['base_minor'] ?? 0),
            perKmMinor: (int) ($data['per_km_minor'] ?? 0),
            perMinMinor: (int) ($data['per_min_minor'] ?? 0),
            minFareMinor: (int) ($data['min_fare_minor'] ?? 0),
            stopFeeMinor: (int) ($data['stop_fee_minor'] ?? 0),
            surcharges: $surcharges,
            roundingStepMinor: max(1, (int) ($data['rounding_step_minor'] ?? 100)),
            rangeLowBp: (int) round(((float) ($data['range_low_pct'] ?? 85)) * 100),
            rangeHighBp: (int) round(((float) ($data['range_high_pct'] ?? 125)) * 100),
            effectiveFrom: CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_from'], $timezone)->utc(),
            effectiveTo: isset($data['effective_to']) && $data['effective_to'] !== '' ? CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_to'], $timezone)->utc() : null,
            notes: self::nullable($data['notes'] ?? null),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'formula_version' => $this->formula,
            'base_minor' => $this->baseMinor,
            'per_km_minor' => $this->perKmMinor,
            'per_min_minor' => $this->perMinMinor,
            'min_fare_minor' => $this->minFareMinor,
            'stop_fee_minor' => $this->stopFeeMinor,
            'attribute_surcharges' => $this->surcharges,
            'rounding_step_minor' => $this->roundingStepMinor,
            'range_low_bp' => $this->rangeLowBp,
            'range_high_bp' => $this->rangeHighBp,
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

    private static function nullable(mixed $value): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        return $text === '' ? null : $text;
    }
}
