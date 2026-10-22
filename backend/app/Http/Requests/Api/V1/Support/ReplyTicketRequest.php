<?php

namespace App\Http\Requests\Api\V1\Support;

use App\DTOs\Support\TicketReplyData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * POST /support/tickets/{ticket}/messages.
 */
class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:'.(int) config('support.tickets.body_max_chars', 4000)],
            'attachments' => ['nullable', 'array', 'max:'.(int) config('support.tickets.attachments_per_message', 3)],
            'attachments.*' => ['file', 'max:'.(int) config('support.tickets.attachment_max_kilobytes', 8192)],
        ];
    }

    public function toDto(): TicketReplyData
    {
        $files = array_values(array_filter((array) $this->file('attachments', []), static fn (mixed $file): bool => $file instanceof UploadedFile));

        return new TicketReplyData((string) $this->input('body'), $files);
    }
}
