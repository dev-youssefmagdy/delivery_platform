<?php

namespace App\Http\Requests\Api\V1\Content;

use Illuminate\Foundation\Http\FormRequest;

class FaqsRequest extends FormRequest
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
            'category' => ['nullable', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'service' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/'],
        ];
    }
}
