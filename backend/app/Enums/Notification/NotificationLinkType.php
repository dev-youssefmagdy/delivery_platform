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
    case Chat = 'chat';
    case Export = 'export';
    case Inbox = 'inbox';
    /** A safety case of the person (link id: incident uuid; a safety support ticket uuid is also possible). */
    case Safety = 'safety';
    /** Admin only: opens the case in the Safety Center. */
    case Incident = 'incident';
}
