<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds `app` / `env` and replaces the value of any context / extra key that names a secret, an OTP, a phone / email / address or a coordinate. Exceptions are left to the
 * formatter (class, message, file, line, trace).
 */
final class RedactingProcessor implements ProcessorInterface
{
    private const PATTERN = '/pass(word)?|secret|token|authorization|api[_-]?key|otp|pin$|card|cvv|credential|dsn|signature|cookie/i';

    /** Personal data and precise location (SRS §13, §23.4): never in a log line, whatever the caller passes. */
    private const PII_PATTERN = '/phone|msisdn|(^|[_-])(email|lat|lng|lon|latitude|longitude|address|iban|national_id|account_number)([_-]|$)/i';

    public function __construct(private readonly string $app, private readonly string $environment) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra) + ['app' => $this->app, 'env' => $this->environment],
        );
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && (preg_match(self::PATTERN, $key) === 1 || preg_match(self::PII_PATTERN, $key) === 1)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
