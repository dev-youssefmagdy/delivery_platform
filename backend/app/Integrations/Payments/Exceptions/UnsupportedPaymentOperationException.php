<?php

namespace App\Integrations\Payments\Exceptions;

/**
 * The driver's processor does not offer this operation (for example capture without authorization).
 */
final class UnsupportedPaymentOperationException extends PaymentGatewayException {}
