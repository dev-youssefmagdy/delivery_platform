<?php

namespace App\Http\Requests\Api\V1\Support;

use App\DTOs\Support\ChatMessageData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * POST /bookings/{booking}/messages and /provider/bookings/{booking}/messages: text and/or one image (multipart). Who may chat
 * and the window are decided by `BookingChatService`; the type, size, decode and scan of the image by the upload pipeline.
 */
class SendChatMessageRequest extends FormRequest
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
            'body' => ['nullable', 'string', 'max:'.((int) config('support.chat.max_chars', 1000) * 2)],
            'attachment' => ['nullable', 'file', 'max:'.(int) config('support.chat.attachment_max_kilobytes', 5120)],
        ];
    }

    public function toDto(): ChatMessageData
    {
        $file = $this->file('attachment');

        return new ChatMessageData($this->filled('body') ? (string) $this->input('body') : null, $file instanceof UploadedFile ? $file : null);
    }
}
