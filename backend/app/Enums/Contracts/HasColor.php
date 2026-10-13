<?php

namespace App\Enums\Contracts;

use App\Enums\Support\BadgeColor;

/**
 * An enum case that is rendered as a coloured badge in the admin panel.
 */
interface HasColor
{
    public function color(): BadgeColor;
}
