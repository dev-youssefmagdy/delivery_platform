<?php

namespace App\Http\Requests\Admin\Support;

use App\DTOs\Support\StaffReplyData;
use App\Enums\Support\TicketVisibility;
use App\Http\Requests\Admin\ModelActionRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * An agent's answer (public) or internal note, with an optional macro it started from and one attachment.
 */
class ReplyTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reply';
    }

    protected function parameter(): string
    {
        return 'ticket';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:'.(int) config('support.tickets.body_max_chars', 4000)],
            'visibility' => ['required', 'string', Rule::enum(TicketVisibility::class)],
            'macro_id' => ['nullable', 'uuid', 'exists:support_macros,uuid'],
            'attachment' => ['nullable', 'file', 'max:'.(int) config('support.tickets.attachment_max_kilobytes', 8192)],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => (string) __('admin.support_tickets.fields.message'),
            'visibility' => (string) __('admin.support_tickets.fields.visibility'),
            'macro_id' => (string) __('admin.support_tickets.fields.macro'),
            'attachment' => (string) __('admin.support_tickets.fields.attachment'),
        ];
    }

    public function toDto(?int $macroId): StaffReplyData
    {
        $file = $this->file('attachment');

        return new StaffReplyData(
            (string) $this->input('body'),
            TicketVisibility::from((string) $this->input('visibility')),
            $macroId,
            $file instanceof UploadedFile ? [$file] : [],
        );
    }

    public function macroUuid(): ?string
    {
        return $this->filled('macro_id') ? (string) $this->input('macro_id') : null;
    }
}
