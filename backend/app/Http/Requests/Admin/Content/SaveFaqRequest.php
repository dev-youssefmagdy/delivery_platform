<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentFaq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveFaqRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $faq = $this->route('contentFaq');

        return $faq instanceof ContentFaq ? Gate::allows('update', $faq) : Gate::allows('create', ContentFaq::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('content_categories', 'id')->where('kind', 'faq')],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'fallback_to_ar' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ]
        + $this->translated('question', 300, true)
        + $this->translated('answer', 20000, true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['question', 'answer'], 'content') + [
            'category_id' => (string) __('content.fields.category'),
            'service_category_id' => (string) __('content.fields.service_category'),
            'sort_order' => (string) __('content.fields.sort_order'),
            'fallback_to_ar' => (string) __('content.fields.fallback_to_ar'),
        ];
    }
}
