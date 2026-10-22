<?php

namespace App\Http\Requests\Api\V1\Support;

use App\DTOs\Support\OpenTicketData;
use App\Enums\Support\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * POST /support/tickets (JSON or multipart with `attachments[]`). Links are public ids; the service checks the person owns them.
 */
class OpenTicketRequest extends FormRequest
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
            'category' => ['required', 'string', Rule::enum(TicketCategory::class)],
            'subject' => ['required', 'string', 'min:3', 'max:'.(int) config('support.tickets.subject_max_chars', 160)],
            'body' => ['required', 'string', 'min:5', 'max:'.(int) config('support.tickets.body_max_chars', 4000)],
            'booking_id' => ['nullable', 'uuid'],
            'payment_id' => ['nullable', 'uuid'],
            'payout_id' => ['nullable', 'uuid'],
            'attachments' => ['nullable', 'array', 'max:'.(int) config('support.tickets.attachments_per_message', 3)],
            'attachments.*' => ['file', 'max:'.(int) config('support.tickets.attachment_max_kilobytes', 8192)],
        ];
    }

    public function toDto(): OpenTicketData
    {
        $files = array_values(array_filter((array) $this->file('attachments', []), static fn (mixed $file): bool => $file instanceof UploadedFile));

        return new OpenTicketData(
            TicketCategory::from((string) $this->input('category')),
            (string) $this->input('subject'),
            (string) $this->input('body'),
            $this->filled('booking_id') ? (string) $this->input('booking_id') : null,
            $this->filled('payment_id') ? (string) $this->input('payment_id') : null,
            $this->filled('payout_id') ? (string) $this->input('payout_id') : null,
            $files,
        );
    }
}
