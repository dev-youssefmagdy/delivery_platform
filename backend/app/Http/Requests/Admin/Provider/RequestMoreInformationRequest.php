<?php

namespace App\Http\Requests\Admin\Provider;

use App\DTOs\Provider\MoreInformationData;
use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Send the application back: a mandatory overall message and optional reasons per pending document.
 */
class RequestMoreInformationRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'verify';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true) + [
            'document_issues' => ['sometimes', 'array', 'max:50'],
            'document_issues.*' => ['nullable', 'string', 'min:'.config('admin.reason.min'), 'max:'.config('admin.reason.max')],
        ];
    }

    public function toDto(): MoreInformationData
    {
        $issues = [];

        foreach ((array) $this->validated('document_issues', []) as $uuid => $reason) {
            if (is_string($reason) && trim($reason) !== '' && preg_match('/^[0-9a-f-]{36}$/i', (string) $uuid) === 1) {
                $issues[(string) $uuid] = trim($reason);
            }
        }

        return new MoreInformationData((string) $this->reason(), $issues);
    }
}
