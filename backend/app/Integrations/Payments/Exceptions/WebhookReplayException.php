<?php

namespace App\Integrations\Payments\Exceptions;

use RuntimeException;

/**
 * The signature is valid but its signed timestamp is outside the accepted window: a captured request replayed later.
 */
final class WebhookReplayException extends RuntimeException {}
