<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\PayoutBatch;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Payout batches: read with `payouts.view`; exporting the transfer file (it carries the decrypted destination) needs `payouts.review`.
 */
class PayoutBatchPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function view(Authorizable $admin, PayoutBatch $batch): bool
    {
        return $admin->can(AdminPermission::PayoutsView->value);
    }

    public function export(Authorizable $admin, PayoutBatch $batch): bool
    {
        return $admin->can(AdminPermission::PayoutsReview->value);
    }
}
