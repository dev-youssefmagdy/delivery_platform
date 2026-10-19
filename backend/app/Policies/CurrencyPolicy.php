<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Currency;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for Currency (Plan Appendix A has a single markets.manage permission for markets).
 */
class CurrencyPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function view(Authorizable $admin, Currency $currency): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function update(Authorizable $admin, Currency $currency): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }

    public function delete(Authorizable $admin, Currency $currency): bool
    {
        return $admin->can(AdminPermission::MarketsManage->value);
    }
}
