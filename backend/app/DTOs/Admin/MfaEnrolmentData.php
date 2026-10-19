<?php

namespace App\DTOs\Admin;

use SensitiveParameter;

/**
 * What the MFA enrolment screen shows: the manual-entry secret, the otpauth URI and the QR code (inline SVG).
 */
final readonly class MfaEnrolmentData
{
    public function __construct(
        #[SensitiveParameter] public string $secret,
        public string $uri,
        public string $qrSvg,
    ) {}

    /**
     * Secret in groups of four for manual entry.
     */
    public function formattedSecret(): string
    {
        return trim(chunk_split($this->secret, 4, ' '));
    }
}
