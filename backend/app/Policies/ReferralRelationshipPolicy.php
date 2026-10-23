<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Growth\RelationshipStatus;
use App\Models\ReferralRelationship;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Referral relationships and rewards (referrals.view); deciding a flagged one needs referrals.review and a relationship under review.
 */
class ReferralRelationshipPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReferralsView->value);
    }

    public function view(Authorizable $admin, ReferralRelationship $relationship): bool
    {
        return $admin->can(AdminPermission::ReferralsView->value);
    }

    public function review(Authorizable $admin, ReferralRelationship $relationship): bool
    {
        return $admin->can(AdminPermission::ReferralsReview->value) && $relationship->status === RelationshipStatus::UnderReview;
    }
}
