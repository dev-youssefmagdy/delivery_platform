<?php

namespace App\Http\Requests\Api\V1\Me;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /me/photo (multipart, field `photo`).
 */
class StorePhotoRequest extends FormRequest
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
            'photo' => [
                'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('identity.photo.max_kilobytes', 5120),
                'dimensions:min_width=64,min_height=64,max_width=8000,max_height=8000',
            ],
        ];
    }
}
