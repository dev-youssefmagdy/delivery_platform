<?php

namespace App\Http\Requests\Admin\Risk;

use Carbon\CarbonImmutable;
use Closure;
use Throwable;

/**
 * Suspend the signal's customer or provider from the risk queue: a mandatory reason and an optional end (`Y-m-d H:i` in the admin display
 * timezone, in the future). Always temporary: a permanent provider suspension is a separate decision in the provider module.
 */
class SuspendSignalSubjectRequest extends RiskSignalActionRequest
{
    protected function ability(): string
    {
        return 'suspend';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'expires_at' => ['nullable', 'date_format:Y-m-d H:i', function (string $attribute, mixed $value, Closure $fail): void {
                $expiresAt = $this->parseExpiry((string) $value);

                if ($expiresAt === null || $expiresAt->lessThanOrEqualTo(now())) {
                    $fail(__('admin.customers.errors.expiry_in_past'));
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['expires_at' => (string) __('admin.customers.fields.suspension_ends')];
    }

    public function expiresAt(): ?CarbonImmutable
    {
        $value = $this->validated('expires_at');

        return is_string($value) && $value !== '' ? $this->parseExpiry($value) : null;
    }

    private function parseExpiry(string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('!Y-m-d H:i', $value, (string) config('admin.display_timezone', 'UTC'))?->utc();
        } catch (Throwable) {
            return null;
        }
    }
}
