<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Risk\RiskSubjectType;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use App\Models\User;
use App\Services\Risk\RiskSubjectResolver;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;

/**
 * Risk signals (risk.view / risk.manage). Working a signal needs `risk.manage` and an active signal; suspending or restricting also needs
 * the permission and the status rules of the account's own policy (customers.suspend / drivers.suspend), so the shortcut never grants
 * more than the customer and provider modules do.
 */
class RiskSignalPolicy
{
    public function __construct(private readonly RiskSubjectResolver $subjects) {}

    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::RiskView->value);
    }

    public function view(Authorizable $admin, RiskSignal $signal): bool
    {
        return $admin->can(AdminPermission::RiskView->value);
    }

    public function dismiss(Authorizable $admin, RiskSignal $signal): bool
    {
        return $this->manage($admin, $signal);
    }

    public function escalate(Authorizable $admin, RiskSignal $signal): bool
    {
        return $this->manage($admin, $signal) && $signal->status->value === 'open';
    }

    public function suspend(Authorizable $admin, RiskSignal $signal): bool
    {
        return $this->manage($admin, $signal) && $this->subjectAllows($admin, $signal, 'suspend');
    }

    public function restrict(Authorizable $admin, RiskSignal $signal): bool
    {
        return $this->manage($admin, $signal)
            && $signal->subject_type === RiskSubjectType::Customer
            && $this->subjectAllows($admin, $signal, 'restrict');
    }

    private function manage(Authorizable $admin, RiskSignal $signal): bool
    {
        return $admin->can(AdminPermission::RiskManage->value) && $signal->status->isActive();
    }

    private function subjectAllows(Authorizable $admin, RiskSignal $signal, string $ability): bool
    {
        $subject = $this->subjects->describe($signal)['model'];

        if (! $subject instanceof User && ! $subject instanceof ProviderProfile) {
            return false;
        }

        return Gate::forUser($admin)->allows($ability, $subject);
    }
}
