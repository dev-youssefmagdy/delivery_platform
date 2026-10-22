<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * When in the job a proof was taken. The server derives it from the booking, the client never chooses it: arrived at
 * pickup = pickup, trip in progress = delivery, trip in progress on the way back to the sender = return.
 */
enum ProofStage: string implements HasLabel
{
    use EnumHelpers;

    case Pickup = 'pickup';
    case Delivery = 'delivery';
    case Return = 'return';

    public function label(): string
    {
        return __("enums.proof_stage.{$this->value}");
    }
}
