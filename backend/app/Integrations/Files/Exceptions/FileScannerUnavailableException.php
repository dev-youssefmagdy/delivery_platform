<?php

namespace App\Integrations\Files\Exceptions;

use RuntimeException;

/**
 * The scan engine could not be reached or answered with garbage. Uploads fail closed.
 */
final class FileScannerUnavailableException extends RuntimeException {}
