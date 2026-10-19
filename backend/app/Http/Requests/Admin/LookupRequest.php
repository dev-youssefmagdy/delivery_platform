<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /admin/lookups/{lookup}?q=…&ids[]=…
 */
class LookupRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:50'],
            'ids.*' => ['string', 'max:64'],
        ];
    }
}
