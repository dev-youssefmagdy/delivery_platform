<?php

namespace App\Integrations\Files\Contracts;

use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\FileScanResult;

/**
 * Scans one uploaded file for malware. The only way the platform reaches an antivirus engine (ADR-12).
 */
interface FileScanner
{
    /**
     * @param string $path readable local path of the file to scan
     *
     * @throws FileScannerUnavailableException when the engine could not give a verdict (the upload is then refused)
     */
    public function scan(string $path): FileScanResult;

    /**
     * Short driver name (`noop`, `clamav`).
     */
    public function name(): string;
}
