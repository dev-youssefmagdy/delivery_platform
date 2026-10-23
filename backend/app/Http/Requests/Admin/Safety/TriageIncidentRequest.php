<?php

namespace App\Http\Requests\Admin\Safety;

/** Change severity and priority (reason mandatory, audited). */
class TriageIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'triage';
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
            'severity' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Safety\IncidentSeverity::class)],
            'priority' => ['required', 'string', \Illuminate\Validation\Rule::enum(\App\Enums\Safety\IncidentPriority::class)],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['severity' => (string) __('safety.admin.fields.severity'), 'priority' => (string) __('safety.admin.fields.priority')];
    }

    public function severity(): \App\Enums\Safety\IncidentSeverity
    {
        return \App\Enums\Safety\IncidentSeverity::from((string) $this->validated('severity'));
    }

    public function priority(): \App\Enums\Safety\IncidentPriority
    {
        return \App\Enums\Safety\IncidentPriority::from((string) $this->validated('priority'));
    }
}
