<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\CommissionType;
use App\Http\Requests\Admin\Finance\StoreCommissionRuleRequest;
use App\Http\Requests\Admin\Finance\UpdateCommissionRuleRequest;
use App\Models\CommissionRule;
use Carbon\CarbonImmutable;

/**
 * Validated input of creating / updating a commission rule. Amounts are integer minor units, the percentage arrives as
 * a percent in the form and is stored in basis points. The terms (type, percent, fixed, min, max, tips) become a new
 * immutable version when they change; the rest edits the rule itself.
 */
final readonly class CommissionRuleData
{
    public function __construct(
        public string $name,
        public ?int $serviceCategoryId,
        public ?int $serviceZoneId,
        public ?string $providerTier,
        public ?string $campaignCode,
        public string $currency,
        public int $priority,
        public CarbonImmutable $effectiveFrom,
        public ?CarbonImmutable $effectiveTo,
        public CommissionType $type,
        public int $percentBp,
        public int $fixedMinor,
        public ?int $minMinor,
        public ?int $maxMinor,
        public bool $commissionOnTips,
        public ?string $notes,
        public ?string $reason,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCommissionRuleRequest|UpdateCommissionRuleRequest $request, string $currency): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('commissionRule');
        $timezone = (string) config('admin.display_timezone', 'UTC');
        $type = CommissionType::from((string) $data['type']);
        $optional = static fn (mixed $value): ?int => is_numeric($value) ? (int) $value : null;

        return new self(
            name: trim((string) $data['name']),
            serviceCategoryId: $current instanceof CommissionRule ? $current->service_category_id : $optional($data['service_category_id'] ?? null),
            serviceZoneId: $current instanceof CommissionRule ? $current->service_zone_id : $optional($data['service_zone_id'] ?? null),
            providerTier: $current instanceof CommissionRule ? $current->provider_tier : self::nullable($data['provider_tier'] ?? null),
            campaignCode: $current instanceof CommissionRule ? $current->campaign_code : self::nullable($data['campaign_code'] ?? null),
            currency: $current instanceof CommissionRule ? $current->currency : $currency,
            priority: (int) ($data['priority'] ?? 0),
            effectiveFrom: CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_from'], $timezone)->utc(),
            effectiveTo: isset($data['effective_to']) && $data['effective_to'] !== '' ? CarbonImmutable::createFromFormat('!Y-m-d H:i', (string) $data['effective_to'], $timezone)->utc() : null,
            type: $type,
            percentBp: $type->usesPercent() ? (int) round(((float) ($data['percent'] ?? 0)) * 100) : 0,
            fixedMinor: $type->usesFixed() ? (int) ($data['fixed_minor'] ?? 0) : 0,
            minMinor: $optional($data['min_minor'] ?? null),
            maxMinor: $optional($data['max_minor'] ?? null),
            commissionOnTips: (bool) ($data['commission_on_tips'] ?? false),
            notes: self::nullable($data['notes'] ?? null),
            reason: self::nullable($data['reason'] ?? null),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Attributes of the rule row (scope and period; the terms live in the version).
     *
     * @return array<string, mixed>
     */
    public function ruleAttributes(): array
    {
        return [
            'name' => $this->name,
            'priority' => $this->priority,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
            'notes' => $this->notes,
        ];
    }

    /**
     * Attributes of a version row.
     *
     * @return array<string, mixed>
     */
    public function versionAttributes(): array
    {
        return [
            'type' => $this->type,
            'percent_bp' => $this->percentBp,
            'fixed_minor' => $this->fixedMinor,
            'min_minor' => $this->minMinor,
            'max_minor' => $this->maxMinor,
            'commission_on_tips' => $this->commissionOnTips,
            'reason' => $this->reason,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
