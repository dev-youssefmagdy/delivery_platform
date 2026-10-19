<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Country;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for Country (Plan Appendix A has a single markets.manage permission for markets).
 */
class CountryPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function view(Authorizable $admin, Country $country): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function update(Authorizable $admin, Country $country): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function delete(Authorizable $admin, Country $country): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }
}
