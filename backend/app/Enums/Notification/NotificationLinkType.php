<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;

/**
 * What a notification opens in the app. The push payload carries `link_type` and `link_id`; the apps map the pair to a
 * screen and tolerate a target that no longer exists (SRS §21).
 */
enum NotificationLinkType: string
{
    use EnumHelpers;

    case Booking = 'booking';
    case Payout = 'payout';
    case Documents = 'documents';
    case Application = 'application';
    case Wallet = 'wallet';
    case Support = 'support';
    case Message = 'message';
    case Export = 'export';
    case Inbox = 'inbox';
}
