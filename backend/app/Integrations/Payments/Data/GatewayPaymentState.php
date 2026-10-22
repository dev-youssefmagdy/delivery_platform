<?php

namespace App\Integrations\Payments\Data;

/**
 * A processor's verdict on one payment, neutral across vendors.
 */
enum GatewayPaymentState: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case NotFound = 'not_found';
}
