<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\CategoryKind;
use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $category = $this->route('contentCategory');

        return $category instanceof ContentCategory ? Gate::allows('update', $category) : Gate::allows('create', ContentCategory::class);
    }

    protected function prepareForValidation(): void
    {
        $category = $this->route('contentCategory');

        if ($category instanceof ContentCategory) {
            $this->merge(['kind' => $category->kind->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('contentCategory');

        return [
            'kind' => ['required', Rule::enum(CategoryKind::class)],
            'slug' => [
                'required', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('content_categories', 'slug')->where('kind', $this->input('kind'))->ignore($category instanceof ContentCategory ? $category->id : null),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ] + $this->translated('name', 120, true) + $this->translated('description', 500);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['name', 'description'], 'content') + [
            'kind' => (string) __('content.fields.category_kind'),
            'slug' => (string) __('content.fields.slug'),
            'sort_order' => (string) __('content.fields.sort_order'),
            'active' => (string) __('content.fields.active'),
        ];
    }
}
