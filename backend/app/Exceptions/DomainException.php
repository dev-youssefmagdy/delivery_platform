<?php

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Base for expected business errors. Carries a stable machine code (`errors._code`), an optional field (so
 * forms/apps can show it inline) and safe context. The message is localized from `lang/{locale}/api.php`
 * key `errors.{code}`, falling back to the generic message for the HTTP status.
 *
 * Implements HttpExceptionInterface so HTML (admin/website) requests get the normal error page for the status.
 */
abstract class DomainException extends RuntimeException implements HttpExceptionInterface
{
    /**
     * @param array<string, mixed> $context safe, non-secret values; may be used as translation replacements
     */
    final public function __construct(
        public readonly string $errorCode,
        public readonly ?string $field = null,
        public readonly array $context = [],
        ?string $message = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?? $this->translatedMessage(), 0, $previous);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function make(string $code, ?string $field = null, array $context = [], ?string $message = null): static
    {
        return new static($code, $field, $context, $message);
    }

    abstract public function getStatusCode(): int;

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Safe machine-readable extras for `meta` (retry hints, remaining attempts): only these context keys are exposed.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return array_intersect_key($this->context, array_flip(['retry_after', 'attempts_remaining']));
    }

    /**
     * Field errors for the envelope.
     *
     * @return array<string, list<string>>
     */
    public function fieldErrors(): array
    {
        return $this->field === null ? [] : [$this->field => [$this->getMessage()]];
    }

    private function translatedMessage(): string
    {
        $key = "api.errors.{$this->errorCode}";
        $replace = array_filter($this->context, static fn (mixed $value): bool => is_scalar($value));
        $translated = __($key, $replace);

        return is_string($translated) && $translated !== $key
            ? $translated
            : (string) __('api.http.'.$this->getStatusCode());
    }
}
