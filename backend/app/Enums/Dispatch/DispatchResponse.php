<?php

namespace App\Enums\Dispatch;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a notified provider did with a dispatched request (`booking_dispatch_recipients.response`).
 */
enum DispatchResponse: string implements HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Offered = 'offered';
    case Declined = 'declined';
    case Expired = 'expired';

    public function label(): string
    {
        return __("enums.dispatch_response.{$this->value}");
    }
}
