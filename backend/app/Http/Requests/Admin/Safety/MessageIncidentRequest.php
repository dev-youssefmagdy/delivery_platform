<?php

namespace App\Http\Requests\Admin\Safety;

/** A message the person reads in the app. */
class MessageIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'message';
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
        return ['body' => ['required', 'string', 'min:2', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['body' => (string) __('safety.admin.fields.message')];
    }
}
