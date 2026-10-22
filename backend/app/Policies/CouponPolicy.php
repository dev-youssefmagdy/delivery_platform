<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Coupon\CouponStatus;
use App\Models\Coupon;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Coupons: `coupons.view` reads (listing, pages, tabs, the eligibility test), `coupons.manage` writes (create, edit, publish,
 * pause, resume, archive), `coupons.export` queues the CSV export. A coupon is never deleted. The state-dependent abilities
 * mirror the lifecycle graph, so the server refuses what the UI hides.
 */
class CouponPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CouponsView->value) || $this->manages($admin);
    }

    public function view(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->viewAny($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->manages($admin);
    }

    public function update(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->manages($admin) && $coupon->status !== CouponStatus::Archived;
    }

    public function publish(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->manages($admin) && $coupon->status === CouponStatus::Draft;
    }

    public function pause(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->manages($admin) && in_array($coupon->status, [CouponStatus::Active, CouponStatus::Scheduled], true);
    }

    public function resume(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->manages($admin) && $coupon->status === CouponStatus::Paused;
    }

    public function archive(Authorizable $admin, Coupon $coupon): bool
    {
        return $this->manages($admin) && $coupon->status !== CouponStatus::Archived;
    }

    public function export(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CouponsExport->value);
    }

    private function manages(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::CouponsManage->value);
    }
}
