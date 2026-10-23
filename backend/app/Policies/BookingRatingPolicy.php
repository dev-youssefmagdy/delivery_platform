<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Reviews\ModerationStatus;
use App\Models\BookingRating;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Reviews (reviews.view / reviews.moderate). There is deliberately NO update or delete ability: an administrator can change a review's
 * visibility (hide, restore, mark under review, with a reason) but never its text, stars or tags (SRS §16.12).
 */
class BookingRatingPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ReviewsView->value);
    }

    public function view(Authorizable $admin, BookingRating $rating): bool
    {
        return $admin->can(AdminPermission::ReviewsView->value);
    }

    public function hide(Authorizable $admin, BookingRating $rating): bool
    {
        return $admin->can(AdminPermission::ReviewsModerate->value) && $rating->moderation_status !== ModerationStatus::Hidden;
    }

    public function restore(Authorizable $admin, BookingRating $rating): bool
    {
        return $admin->can(AdminPermission::ReviewsModerate->value) && $rating->moderation_status !== ModerationStatus::Visible;
    }

    public function markUnderReview(Authorizable $admin, BookingRating $rating): bool
    {
        return $admin->can(AdminPermission::ReviewsModerate->value) && $rating->moderation_status === ModerationStatus::Visible;
    }
}
