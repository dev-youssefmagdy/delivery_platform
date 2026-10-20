<?php

namespace App\Integrations\Files\Drivers;

use App\Enums\Provider\FileScanStatus;
use App\Integrations\Files\Contracts\FileScanner;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\FileScanResult;

/**
 * ClamAV through the clamd INSTREAM protocol over TCP (`CLAMAV_HOST`, `CLAMAV_PORT`). Implemented from the clamd
 * documentation and tested against its reply format only (no clamd server runs in CI): verify on staging.
 */
final class ClamAvFileScanner implements FileScanner
{
    private const CHUNK_BYTES = 8192;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly int $timeoutSeconds,
    ) {}

    public function scan(string $path): FileScanResult
    {
        $file = @fopen($path, 'rb');

        if ($file === false) {
            throw new FileScannerUnavailableException('The file to scan could not be read.');
        }

        $socket = @stream_socket_client("tcp://{$this->host}:{$this->port}", $errorCode, $errorMessage, $this->timeoutSeconds);

        if ($socket === false) {
            fclose($file);

            throw new FileScannerUnavailableException('The antivirus engine is not reachable.');
        }

        try {
            stream_set_timeout($socket, $this->timeoutSeconds);
            fwrite($socket, "zINSTREAM\0");

            while (! feof($file)) {
                $chunk = fread($file, self::CHUNK_BYTES);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            fwrite($socket, pack('N', 0));
            $reply = stream_get_contents($socket);
        } finally {
            fclose($file);
            fclose($socket);
        }

        return self::parseReply(is_string($reply) ? $reply : '');
    }

    public function name(): string
    {
        return 'clamav';
    }

    /**
     * Turns a clamd reply (`stream: OK`, `stream: Eicar-Test-Signature FOUND`, `... ERROR`) into a verdict.
     *
     * @throws FileScannerUnavailableException for errors and unknown replies
     */
    public static function parseReply(string $reply): FileScanResult
    {
        $reply = trim($reply, "\0 \n\r\t");

        if (str_ends_with($reply, ' OK')) {
            return new FileScanResult(FileScanStatus::Clean);
        }

        if (preg_match('/^stream:\s*(.+)\s+FOUND$/', $reply, $matches) === 1) {
            return new FileScanResult(FileScanStatus::Infected, substr($matches[1], 0, 120));
        }

        throw new FileScannerUnavailableException('The antivirus engine gave no usable verdict.');
    }
}
