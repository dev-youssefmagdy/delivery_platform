<?php

namespace App\Integrations\Sms\Exceptions;

use RuntimeException;

/**
 * The SMS provider rejected or failed to deliver a message. The message never contains the text or credentials.
 */
final class SmsDeliveryException extends RuntimeException {}
