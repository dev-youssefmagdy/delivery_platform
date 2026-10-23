<?php

namespace App\Http\Requests\Api\V1\Content;

use App\Enums\Content\ArticleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticlesRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(ArticleType::class)],
            'category' => ['nullable', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
