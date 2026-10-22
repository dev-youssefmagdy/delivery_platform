<?php

namespace App\Integrations\Payments\Exceptions;

use RuntimeException;

/**
 * The callback was not signed by the processor (or the signature is missing / malformed).
 */
final class InvalidWebhookSignatureException extends RuntimeException {}
