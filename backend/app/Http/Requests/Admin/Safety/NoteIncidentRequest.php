<?php

namespace App\Http\Requests\Admin\Safety;

/** A staff-only note on a case. */
class NoteIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'note';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:4000']];
    }

    public function attributes(): array
    {
        return ['body' => (string) __('safety.admin.fields.note')];
    }
}
