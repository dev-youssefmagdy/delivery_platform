<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\GatewayWebhookEvent;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Gateway webhook events: read with `payments.view`; processing a failed / unmatched / mismatching event again needs
 * `finance.reconcile` (and a reason). Events themselves are never edited or deleted.
 */
class GatewayWebhookEventPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PaymentsView->value);
    }

    public function view(Authorizable $admin, GatewayWebhookEvent $event): bool
    {
        return $admin->can(AdminPermission::PaymentsView->value);
    }

    public function reprocess(Authorizable $admin, GatewayWebhookEvent $event): bool
    {
        return $admin->can(AdminPermission::FinanceReconcile->value)
            && $event->signature_valid
            && $event->processed_at === null
            && $event->outcome->canReprocess();
    }
}
