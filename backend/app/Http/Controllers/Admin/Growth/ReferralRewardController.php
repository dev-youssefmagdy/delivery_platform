<?php

namespace App\Http\Controllers\Admin\Growth;

use App\DataTables\Admin\Growth\ReferralRewardsDataTable;
use App\Http\Controllers\Admin\AdminController;

/**
 * Referral rewards listing (referrals.view), read-only.
 */
final class ReferralRewardController extends AdminController
{
    public function index(ReferralRewardsDataTable $table): mixed
    {
        return $table->render('admin.growth.referral-rewards.index');
    }
}
