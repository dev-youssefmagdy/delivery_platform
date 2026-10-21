<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Models\BookingOffer;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Admin access to the Offers monitor (offers.view). Offers are read-only here: `offers.moderate` is for the audited
 * corrective workflow of a later milestone, never for editing an accepted offer (SRS §16.7).
 */
class BookingOfferPolicy
{
    public function viewAny(Authorizable $actor): bool
    {
        return $actor instanceof Admin && $actor->can(AdminPermission::OffersView->value);
    }

    public function view(Authorizable $actor, BookingOffer $offer): bool
    {
        return $this->viewAny($actor);
    }
}
