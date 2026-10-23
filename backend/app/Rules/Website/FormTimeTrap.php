<?php

namespace App\Rules\Website;

use App\Support\Website\FormGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The time-trap half of the public form protection (see FormGuard).
 */
final class FormTimeTrap implements ValidationRule
{
    public function __construct(private readonly FormGuard $guard) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = $this->guard->check($value);

        if ($error !== null) {
            $fail("site.forms.errors.{$error}")->translate();
        }
    }
}
