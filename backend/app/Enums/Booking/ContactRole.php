<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The party a parcel contact stands for. The sender is met at pickup, the recipient at drop-off.
 */
enum ContactRole: string implements HasLabel
{
    use EnumHelpers;

    case Sender = 'sender';
    case Recipient = 'recipient';

    /**
     * The service form field that carries this contact (`service_details.<key>`).
     */
    public function fieldKey(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return __("enums.contact_role.{$this->value}");
    }
}
