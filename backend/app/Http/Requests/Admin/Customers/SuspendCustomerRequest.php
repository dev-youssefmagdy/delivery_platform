<?php

namespace App\Http\Requests\Admin\Customers;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Dangerous action: a mandatory reason and an optional end of the suspension (`Y-m-d H:i` in the admin display
 * timezone, in the future). Without an end the suspension lasts until an admin reactivates the account.
 */
class SuspendCustomerRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof User && Gate::allows('suspend', $customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true) + [
            'expires_at' => ['nullable', 'date_format:Y-m-d H:i', function (string $attribute, mixed $value, Closure $fail): void {
                $expiresAt = $this->parseExpiry((string) $value);

                if ($expiresAt === null || $expiresAt->lessThanOrEqualTo(now())) {
                    $fail(__('admin.customers.errors.expiry_in_past'));
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'expires_at' => (string) __('admin.customers.fields.suspension_ends')];
    }

    /**
     * The end of the suspension in UTC, or null for an open-ended one.
     */
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
