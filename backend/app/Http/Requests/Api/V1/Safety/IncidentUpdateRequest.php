<?php

namespace App\Http\Requests\Api\V1\Safety;

use Illuminate\Foundation\Http\FormRequest;

class IncidentUpdateRequest extends FormRequest
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
        return ['body' => ['required', 'string', 'min:2', 'max:'.(int) config('safety.reports.update_max_chars', 2000)]];
    }
}
