<?php

namespace App\Http\Requests\Admin;

use App\Enums\Common\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Topbar locale switcher.
 */
class UpdateLocaleRequest extends FormRequest
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
            'locale' => ['required', 'string', Rule::enum(Locale::class)],
        ];
    }

    public function locale(): Locale
    {
        return Locale::from((string) $this->validated('locale'));
    }
}
