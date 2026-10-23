<?php

namespace App\DTOs\System;

use App\Enums\System\AppTarget;

/**
 * Who a flag is evaluated for: the app, the city and a stable subject (user uuid, device id) used for the percentage bucket.
 * Anything unknown is null; a flag that targets cities or a percentage is then off (the safe answer).
 */
final readonly class FlagContext
{
    public function __construct(public ?AppTarget $app = null, public ?int $cityId = null, public ?string $subject = null) {}
}
