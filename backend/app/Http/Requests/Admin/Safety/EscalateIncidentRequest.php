<?php

namespace App\Http\Requests\Admin\Safety;

/** Escalate a case (level and reason mandatory). */
class EscalateIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'escalate';
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
        return parent::rules() + ['level' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Safety\IncidentEscalationLevel::class)]];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['level' => (string) __('safety.admin.fields.escalation_level')];
    }

    public function level(): \App\Enums\Safety\IncidentEscalationLevel
    {
        return \App\Enums\Safety\IncidentEscalationLevel::from((string) $this->validated('level'));
    }
}
