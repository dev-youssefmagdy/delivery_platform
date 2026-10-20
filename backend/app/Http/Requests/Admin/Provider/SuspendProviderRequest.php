<?php

namespace App\Http\Requests\Admin\Provider;

use App\DTOs\Provider\SuspendProviderData;
use App\Http\Requests\Admin\ModelActionRequest;
use Carbon\CarbonImmutable;
use Closure;
use Throwable;

/**
 * Dangerous action: a mandatory reason, temporary (optional end, `Y-m-d H:i` in the admin timezone, in the future) or
 * permanent. A permanent suspension cannot be lifted from the panel.
 */
class SuspendProviderRequest extends ModelActionRequest
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
        return $this->reasonRules(true) + [
            'permanent' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date_format:Y-m-d H:i', 'prohibited_if:permanent,1,true,on', function (string $attribute, mixed $value, Closure $fail): void {
                $expiresAt = $this->parseExpiry((string) $value);

                if ($expiresAt === null || $expiresAt->lessThanOrEqualTo(now())) {
                    $fail(__('admin.providers.errors.expiry_in_past'));
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'expires_at' => (string) __('admin.providers.fields.suspension_ends')];
    }

    public function toDto(): SuspendProviderData
    {
        $expires = $this->validated('expires_at');

        return new SuspendProviderData(
            reason: (string) $this->reason(),
            permanent: $this->boolean('permanent'),
            expiresAt: is_string($expires) && $expires !== '' ? $this->parseExpiry($expires) : null,
        );
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
