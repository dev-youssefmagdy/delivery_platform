<?php

namespace App\Support\Audit;

/**
 * Gives a model a safe snapshot for the audit log. Hidden attributes, timestamps listed in
 * `$auditExcluded` and anything secret/PII must never appear in `auditSnapshot()`.
 *
 * Declare on the model: `protected array $auditExcluded = ['otp_hash', ...];`
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Auditable
{
    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        $excluded = array_merge($this->getHidden(), $this->auditExcludedAttributes());

        return array_diff_key($this->attributesToArray(), array_flip($excluded));
    }

    /**
     * @return list<string>
     */
    public function auditExcludedAttributes(): array
    {
        /** @var list<string> $excluded */
        $excluded = property_exists($this, 'auditExcluded') ? $this->auditExcluded : [];

        return $excluded;
    }
}
