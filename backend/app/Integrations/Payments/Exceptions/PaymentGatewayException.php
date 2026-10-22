<?php

namespace App\Integrations\Payments\Exceptions;

use RuntimeException;

/**
 * The processor failed, refused or could not be reached. The message never contains credentials, card data or the
 * request body.
 */
class PaymentGatewayException extends RuntimeException {}
