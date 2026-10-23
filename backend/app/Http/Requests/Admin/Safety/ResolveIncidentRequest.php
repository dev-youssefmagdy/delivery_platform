<?php

namespace App\Http\Requests\Admin\Safety;

/** Resolve a case with an outcome and a mandatory written reason. */
class ResolveIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'resolve';
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
        return parent::rules() + [
            'outcome' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Safety\IncidentOutcome::class)],
            'public_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['outcome' => (string) __('safety.admin.fields.outcome'), 'public_message' => (string) __('safety.admin.fields.public_message')];
    }

    public function outcome(): \App\Enums\Safety\IncidentOutcome
    {
        return \App\Enums\Safety\IncidentOutcome::from((string) $this->validated('outcome'));
    }

    public function publicMessage(): ?string
    {
        $message = $this->validated('public_message');

        return is_string($message) && trim($message) !== '' ? trim($message) : null;
    }
}
