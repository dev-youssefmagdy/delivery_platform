<?php

namespace App\Http\Requests\Admin\Concerns;

/**
 * Reason field of dangerous actions (confirm dialog textarea). Length limits come from config('admin.reason').
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait HasReason
{
    /**
     * @return array<string, mixed>
     */
    protected function reasonRules(bool $required): array
    {
        return [
            'reason' => [$required ? 'required' : 'nullable', 'string', 'min:'.config('admin.reason.min'), 'max:'.config('admin.reason.max')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason')];
    }

    public function reason(): ?string
    {
        $reason = $this->validated('reason');

        return is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
    }
}
