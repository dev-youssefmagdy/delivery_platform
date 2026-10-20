<?php

namespace App\Support\Files;

/**
 * An upload that passed every check: the bytes to store (images are re-encoded), their MIME type and extension.
 */
final readonly class InspectedUpload
{
    public function __construct(
        public string $bytes,
        public string $mime,
        public string $extension,
        public string $originalName,
    ) {}

    public function size(): int
    {
        return strlen($this->bytes);
    }

    public function hash(): string
    {
        return hash('sha256', $this->bytes);
    }
}
