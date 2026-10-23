<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Validation\Rule;

/** Decide an appeal: `decision` is `uphold` or `reject`; the reason is mandatory and is shown to nobody but staff and the audit trail. */
class DecideAppealRequest extends ModelActionRequest
{
    protected function parameter(): string
    {
        return 'appeal';
    }

    protected function ability(): string
    {
        return 'decide';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['decision' => ['required', 'string', Rule::in(['uphold', 'reject'])]];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['decision' => (string) __('reputation.admin.fields.decision')];
    }

    public function uphold(): bool
    {
        return $this->validated('decision') === 'uphold';
    }
}
