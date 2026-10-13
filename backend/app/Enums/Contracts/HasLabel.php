<?php

namespace App\Enums\Contracts;

/**
 * An enum case that has a localized, user-visible label.
 */
interface HasLabel
{
    public function label(): string;
}
