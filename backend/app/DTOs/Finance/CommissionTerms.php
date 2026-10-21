<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\CommissionType;
use App\Models\CommissionRuleVersion;
use Carbon\CarbonInterface;

/**
 * The numbers of one commission, as they applied to a booking. This is what the booking stores (its snapshot): later
 * edits of the rule never change it (Plan I4). `ruleId`/`versionId` are null for the platform default (setting).
 */
final readonly class CommissionTerms
{
    public const SOURCE_RULE = 'rule';

    public const SOURCE_DEFAULT = 'default';

    public function __construct(
        public CommissionType $type,
        public int $percentBp,
        public int $fixedMinor,
        public ?int $minMinor,
        public ?int $maxMinor,
        public bool $commissionOnTips,
        public string $currency,
        public string $source = self::SOURCE_RULE,
        public ?int $ruleId = null,
        public ?int $versionId = null,
        public ?int $version = null,
        public ?string $ruleName = null,
        public ?string $resolvedAt = null,
    ) {}

    public static function fromVersion(CommissionRuleVersion $version, string $ruleName, string $currency, ?CarbonInterface $at = null): self
    {
        return new self(
            type: $version->type,
            percentBp: $version->percent_bp,
            fixedMinor: $version->fixed_minor,
            minMinor: $version->min_minor,
            maxMinor: $version->max_minor,
            commissionOnTips: $version->commission_on_tips,
            currency: $currency,
            source: self::SOURCE_RULE,
            ruleId: $version->commission_rule_id,
            versionId: $version->id,
            version: $version->version,
            ruleName: $ruleName,
            resolvedAt: $at?->toIso8601String(),
        );
    }

    public static function platformDefault(int $percentBp, string $currency, ?CarbonInterface $at = null): self
    {
        return new self(CommissionType::Percentage, $percentBp, 0, null, null, false, $currency, self::SOURCE_DEFAULT, resolvedAt: $at?->toIso8601String());
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self(
            type: CommissionType::from((string) $snapshot['type']),
            percentBp: (int) $snapshot['percent_bp'],
            fixedMinor: (int) $snapshot['fixed_minor'],
            minMinor: isset($snapshot['min_minor']) ? (int) $snapshot['min_minor'] : null,
            maxMinor: isset($snapshot['max_minor']) ? (int) $snapshot['max_minor'] : null,
            commissionOnTips: (bool) ($snapshot['commission_on_tips'] ?? false),
            currency: (string) $snapshot['currency'],
            source: (string) ($snapshot['source'] ?? self::SOURCE_RULE),
            ruleId: isset($snapshot['rule_id']) ? (int) $snapshot['rule_id'] : null,
            versionId: isset($snapshot['version_id']) ? (int) $snapshot['version_id'] : null,
            version: isset($snapshot['version']) ? (int) $snapshot['version'] : null,
            ruleName: isset($snapshot['rule_name']) ? (string) $snapshot['rule_name'] : null,
            resolvedAt: isset($snapshot['resolved_at']) ? (string) $snapshot['resolved_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'source' => $this->source,
            'rule_id' => $this->ruleId,
            'version_id' => $this->versionId,
            'version' => $this->version,
            'rule_name' => $this->ruleName,
            'type' => $this->type->value,
            'percent_bp' => $this->percentBp,
            'fixed_minor' => $this->fixedMinor,
            'min_minor' => $this->minMinor,
            'max_minor' => $this->maxMinor,
            'commission_on_tips' => $this->commissionOnTips,
            'currency' => $this->currency,
            'resolved_at' => $this->resolvedAt,
        ];
    }
}
