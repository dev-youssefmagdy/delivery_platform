<?php

namespace App\Support\Geo;

use InvalidArgumentException;

/**
 * A latitude/longitude pair that cannot describe a real position (SRS §10.3: reject impossible values).
 */
final class InvalidCoordinatesException extends InvalidArgumentException {}
