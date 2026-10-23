<?php

namespace App\Http\Requests\Api\V1\Safety;

use Illuminate\Foundation\Http\FormRequest;

class IncidentAttachmentRequest extends FormRequest
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
        return ['file' => ['required', 'file', 'max:'.(int) config('safety.attachments.max_kilobytes', 8192)]];
    }
}
