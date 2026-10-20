<?php

namespace App\Integrations\Maps\Exceptions;

use RuntimeException;

/**
 * The provider works but found no drivable route between the points.
 */
final class NoRouteException extends RuntimeException {}
