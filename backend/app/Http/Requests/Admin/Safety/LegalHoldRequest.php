<?php

namespace App\Http\Requests\Admin\Safety;

/** Set or release the legal hold (reason mandatory). */
class LegalHoldRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'legalHold';
    }

    protected function reasonIsRequired(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['hold' => ['required', 'boolean']];
    }

    public function hold(): bool
    {
        return (bool) $this->validated('hold');
    }
}
