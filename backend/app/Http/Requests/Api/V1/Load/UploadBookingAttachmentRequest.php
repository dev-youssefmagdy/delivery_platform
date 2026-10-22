<?php

namespace App\Http\Requests\Api\V1\Load;

use App\Models\BookingAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/attachments (multipart): one photo, video or document of a moving / freight request. Shape checks only; the service
 * sniffs, re-encodes, scans and enforces the per-customer limit.
 */
class UploadBookingAttachmentRequest extends FormRequest
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
            'kind' => ['required', 'string', Rule::in([BookingAttachment::KIND_PHOTO, BookingAttachment::KIND_VIDEO, BookingAttachment::KIND_DOCUMENT])],
            'file' => ['required', 'file', 'max:'.(int) config('offer_scope.attachments.video_max_kilobytes', 51200)],
        ];
    }

    public function kind(): string
    {
        return (string) $this->input('kind');
    }
}
