<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CommissionRuleData;
use App\DTOs\Finance\CommissionTerms;
use App\DTOs\Finance\DiscountFundingSplit;
use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Finance\PriceBreakdownInput;
use App\Enums\Common\AuditAction;
use App\Enums\Finance\DiscountFunding;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\CommissionRule;
use App\Models\CommissionRuleVersion;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin configuration of commission rules (SRS §12.4): audited writes, optimistic locking and immutable versions. A rule
 * is never deleted (its versions are history and bookings point at them); it is disabled. Changing the terms adds a
 * version and moves `current_version_id`; changing only the name, period, priority or notes edits the rule row. New
 * bookings use the rule resolved at selection time; bookings already selected keep their snapshot (Plan I4).
 */
final class CommissionRuleService
{
    private const MAX_MINOR = 10_000_000_000;

    public function __construct(
        private readonly CommissionRuleRepositoryInterface $rules,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceZoneRepositoryInterface $zones,
        private readonly PriceBreakdownCalculator $calculator,
        private readonly SettingsService $settings,
        private readonly CurrencyRegistry $currencies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(CommissionRuleData $data, ?Admin $actor): CommissionRule
    {
        $this->assertValid($data);

        return $this->db->transaction(function () use ($data, $actor): CommissionRule {
            /** @var CommissionRule $rule */
            $rule = $this->rules->create($data->ruleAttributes() + [
                'service_category_id' => $data->serviceCategoryId,
                'service_zone_id' => $data->serviceZoneId,
                'provider_tier' => $data->providerTier,
                'campaign_code' => $data->campaignCode,
                'currency' => $data->currency,
                'active' => true,
            ]);

            $this->addVersion($rule, $data, $actor);
            $this->audit->record(AuditAction::Created, $rule, $actor, after: $this->snapshot($rule));

            return $rule;
        });
    }

    public function update(CommissionRule $rule, CommissionRuleData $data, ?Admin $actor): CommissionRule
    {
        $this->assertValid($data);

        return $this->db->transaction(function () use ($rule, $data, $actor): CommissionRule {
            $locked = $this->lock($rule);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $this->snapshot($locked);
            $this->rules->update($locked, $data->ruleAttributes() + ['lock_version' => $locked->lock_version + 1]);

            if ($this->termsChanged($locked, $data)) {
                $this->addVersion($locked, $data, $actor);
            }

            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $this->snapshot($locked->refresh()), $data->reason);

            return $locked;
        });
    }

    public function setActive(CommissionRule $rule, bool $active, ?string $reason, ?Admin $actor): CommissionRule
    {
        return $this->db->transaction(function () use ($rule, $active, $reason, $actor): CommissionRule {
            $locked = $this->lock($rule);

            if ($locked->active === $active) {
                return $locked;
            }

            $this->rules->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    /**
     * What the rule's current terms do to a fare (the admin's "try it" calculator). Pure: nothing is stored.
     */
    public function preview(CommissionRule $rule, int $fareMinor, int $tipMinor): PriceBreakdown
    {
        $version = $rule->currentVersion ?? throw BusinessRuleViolation::make('commission_rule_without_version');
        $currency = $this->currencies->get($rule->currency);

        return $this->calculator->calculate(new PriceBreakdownInput(
            agreedFare: Money::of($fareMinor, $currency),
            addons: Money::zero($currency),
            discount: Money::zero($currency),
            funding: DiscountFundingSplit::of(DiscountFunding::Platform),
            customerFee: Money::of($this->settings->int(SettingKey::CustomerServiceFeeMinor), $currency),
            taxRateBp: $this->settings->int(SettingKey::TaxRateBasisPoints),
            tip: Money::of($tipMinor, $currency),
            commission: CommissionTerms::fromVersion($version, $rule->name, $rule->currency),
        ));
    }

    private function addVersion(CommissionRule $rule, CommissionRuleData $data, ?Admin $actor): CommissionRuleVersion
    {
        $version = $this->rules->addVersion($rule, $data->versionAttributes() + [
            'version' => $this->rules->nextVersionNumber($rule),
            'created_by_id' => $actor?->id,
        ]);

        $this->rules->update($rule, ['current_version_id' => $version->id]);

        return $version;
    }

    private function termsChanged(CommissionRule $rule, CommissionRuleData $data): bool
    {
        $current = $rule->currentVersion;

        return ! $current instanceof CommissionRuleVersion
            || $current->type !== $data->type
            || $current->percent_bp !== $data->percentBp
            || $current->fixed_minor !== $data->fixedMinor
            || $current->min_minor !== $data->minMinor
            || $current->max_minor !== $data->maxMinor
            || $current->commission_on_tips !== $data->commissionOnTips;
    }

    private function assertValid(CommissionRuleData $data): void
    {
        if ($data->serviceCategoryId !== null && $this->categories->find($data->serviceCategoryId) === null) {
            throw BusinessRuleViolation::make('commission_rule_category_missing', 'service_category_id');
        }

        if ($data->serviceZoneId !== null && $this->zones->find($data->serviceZoneId) === null) {
            throw BusinessRuleViolation::make('commission_rule_zone_missing', 'service_zone_id');
        }

        if ($data->effectiveTo !== null && $data->effectiveTo->lessThanOrEqualTo($data->effectiveFrom)) {
            throw BusinessRuleViolation::make('commission_rule_window_invalid', 'effective_to');
        }

        if ($data->percentBp < 0 || $data->percentBp > 10_000) {
            throw BusinessRuleViolation::make('commission_rule_percent_invalid', 'percent');
        }

        if ($data->fixedMinor < 0 || $data->fixedMinor > self::MAX_MINOR) {
            throw BusinessRuleViolation::make('commission_rule_amount_invalid', 'fixed_minor');
        }

        if ($data->minMinor !== null && $data->maxMinor !== null && $data->maxMinor < $data->minMinor) {
            throw BusinessRuleViolation::make('commission_rule_bounds_invalid', 'max_minor');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(CommissionRule $rule): array
    {
        $rule->loadMissing('currentVersion');
        $version = $rule->currentVersion;

        return $rule->auditSnapshot() + ($version instanceof CommissionRuleVersion ? [
            'version' => $version->version,
            'type' => $version->type->value,
            'percent_bp' => $version->percent_bp,
            'fixed_minor' => $version->fixed_minor,
            'min_minor' => $version->min_minor,
            'max_minor' => $version->max_minor,
            'commission_on_tips' => $version->commission_on_tips,
        ] : []);
    }

    private function lock(CommissionRule $rule): CommissionRule
    {
        $locked = $this->rules->lockById($rule->id);

        if (! $locked instanceof CommissionRule) {
            throw (new ModelNotFoundException)->setModel(CommissionRule::class);
        }

        return $locked->loadMissing('currentVersion');
    }
}
