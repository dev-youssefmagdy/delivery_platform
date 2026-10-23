<?php

namespace App\Http\Requests\Website\Concerns;

use App\Rules\Website\FormTimeTrap;
use App\Support\Auth\PhoneNumber;
use App\Support\Website\FormGuard;

/**
 * Shared protection and input cleaning of the public website forms: honeypot (must stay empty), time trap (hidden encrypted
 * render timestamp), trimmed text, and phone numbers read as E.164 (a local number uses the configured calling code).
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait GuardsPublicForm
{
    /**
     * @return array<string, mixed>
     */
    protected function guardRules(): array
    {
        return [
            FormGuard::HONEYPOT => ['prohibited'],
            FormGuard::TOKEN_FIELD => ['required', 'string', new FormTimeTrap(app(FormGuard::class))],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function guardMessages(): array
    {
        return [
            FormGuard::HONEYPOT.'.prohibited' => (string) __('site.forms.errors.form_invalid'),
            FormGuard::TOKEN_FIELD.'.required' => (string) __('site.forms.errors.form_invalid'),
        ];
    }

    protected function normalizePhoneInput(string $key = 'phone'): void
    {
        $raw = $this->input($key);

        if (! is_string($raw) || trim($raw) === '') {
            $this->merge([$key => null]);

            return;
        }

        $normalized = PhoneNumber::normalize($raw);

        if ($normalized === null) {
            $digits = (string) preg_replace('/[^0-9]/', '', strtr($raw, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));

            if (preg_match('/^0[1-9][0-9]{7,11}$/', $digits) === 1 && ! str_starts_with(trim($raw), '+') && ! str_starts_with($digits, '00')) {
                $normalized = PhoneNumber::normalize((string) config('website.contact.default_phone_code').substr($digits, 1));
            }
        }

        $this->merge([$key => $normalized ?? $raw]);
    }

    protected function trimmed(string $key): ?string
    {
        $value = $this->input($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
