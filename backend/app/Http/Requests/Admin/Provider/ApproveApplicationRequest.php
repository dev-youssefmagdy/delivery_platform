<?php

namespace App\Http\Requests\Admin\Provider;

use App\DTOs\Provider\ApproveApplicationData;
use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve an application: which requested services are approved (at least one) and an optional note.
 */
class ApproveApplicationRequest extends ModelActionRequest
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
        return [
            'approved_services' => ['required', 'array', 'min:1', 'max:50'],
            'approved_services.*' => ['integer', 'min:1', 'distinct'],
            'note' => ['nullable', 'string', 'max:'.config('admin.reason.max')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['approved_services' => (string) __('admin.providers.fields.approved_services'), 'note' => (string) __('admin.providers.fields.note')];
    }

    public function toDto(): ApproveApplicationData
    {
        $note = $this->validated('note');

        return new ApproveApplicationData(
            array_values(array_map('intval', (array) $this->validated('approved_services'))),
            is_string($note) && trim($note) !== '' ? trim($note) : null,
        );
    }
}
