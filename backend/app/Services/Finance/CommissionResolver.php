<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CommissionContext;
use App\DTOs\Finance\CommissionTerms;
use App\Enums\Finance\CommissionScope;
use App\Enums\Settings\SettingKey;
use App\Models\CommissionRule;
use App\Models\CommissionRuleVersion;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Services\Settings\SettingsService;

/**
 * Picks the commission that applies to a booking (SRS §12.4). Among the enabled rules in effect whose every scope
 * matches the context, the winner is chosen by, in order:
 *
 * 1. higher `priority` (an explicit override always wins);
 * 2. higher specificity: campaign (8) > provider tier (4) > zone (2) > service (1), summed over the scopes the rule sets;
 * 3. later `effective_from` (the newer rule);
 * 4. higher id (a stable last tie-break).
 *
 * With no matching rule the platform default (`default_commission_basis_points`) applies, so a booking always gets a
 * commission. The result is a value (CommissionTerms): callers store it as the booking's snapshot.
 */
final class CommissionResolver
{
    public function __construct(
        private readonly CommissionRuleRepositoryInterface $rules,
        private readonly SettingsService $settings,
    ) {}

    public function resolve(CommissionContext $context): CommissionTerms
    {
        $winner = $this->rules->candidatesFor($context)
            ->filter(static fn (CommissionRule $rule): bool => $rule->currentVersion instanceof CommissionRuleVersion)
            ->sort(fn (CommissionRule $a, CommissionRule $b): int => [$b->priority, $this->specificity($b), $b->effective_from->getTimestamp(), $b->id]
                <=> [$a->priority, $this->specificity($a), $a->effective_from->getTimestamp(), $a->id])
            ->first();

        if ($winner instanceof CommissionRule && $winner->currentVersion instanceof CommissionRuleVersion) {
            return CommissionTerms::fromVersion($winner->currentVersion, $winner->name, $winner->currency, $context->at);
        }

        return CommissionTerms::platformDefault($this->settings->int(SettingKey::DefaultCommissionBasisPoints), $context->currency, $context->at);
    }

    private function specificity(CommissionRule $rule): int
    {
        return ($rule->service_category_id !== null ? CommissionScope::ServiceCategory->weight() : 0)
            + ($rule->service_zone_id !== null ? CommissionScope::Zone->weight() : 0)
            + ($rule->provider_tier !== null ? CommissionScope::ProviderTier->weight() : 0)
            + ($rule->campaign_code !== null ? CommissionScope::Campaign->weight() : 0);
    }
}
