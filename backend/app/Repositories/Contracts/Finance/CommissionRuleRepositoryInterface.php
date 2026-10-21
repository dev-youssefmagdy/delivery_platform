<?php

namespace App\Repositories\Contracts\Finance;

use App\DTOs\Finance\CommissionContext;
use App\DTOs\Finance\CommissionRuleFilterData;
use App\Models\CommissionRule;
use App\Models\CommissionRuleVersion;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Commission rules and their immutable versions. Rules are disabled, never deleted (their versions are history).
 *
 * @extends BaseRepositoryInterface<CommissionRule>
 */
interface CommissionRuleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<CommissionRule>
     */
    public function datatableQuery(CommissionRuleFilterData $filters): Builder;

    /**
     * Enabled rules in effect at the context's date whose every scope matches (an unset scope matches anything; a set
     * scope must equal the context's), in the context's currency, with their current version loaded.
     *
     * @return Collection<int, CommissionRule>
     */
    public function candidatesFor(CommissionContext $context): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function addVersion(CommissionRule $rule, array $attributes): CommissionRuleVersion;

    public function nextVersionNumber(CommissionRule $rule): int;

    /**
     * Bookings whose commission snapshot came from this rule.
     */
    public function bookingCount(CommissionRule $rule): int;
}
