<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\ArticleType;
use App\Enums\Content\BodyFormat;
use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveArticleRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $article = $this->route('contentArticle');

        return $article instanceof ContentArticle ? Gate::allows('update', $article) : Gate::allows('create', ContentArticle::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $article = $this->route('contentArticle');

        return [
            'type' => ['required', Rule::enum(ArticleType::class)],
            'slug' => ['required', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_articles', 'slug')->ignore($article instanceof ContentArticle ? $article->id : null)],
            'category_id' => ['nullable', 'integer', Rule::exists('content_categories', 'id')->where('kind', 'article')],
            'body_format' => ['required', Rule::enum(BodyFormat::class)],
            'cover_media_id' => ['nullable', 'integer', 'exists:content_media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'fallback_to_ar' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ]
        + $this->translated('title', 191, true)
        + $this->translated('summary', 500)
        + $this->translated('body', 100000, true)
        + $this->seoRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['title', 'summary', 'body'], 'content') + $this->seoLabels() + [
            'type' => (string) __('content.fields.article_type'),
            'slug' => (string) __('content.fields.slug'),
            'category_id' => (string) __('content.fields.category'),
            'body_format' => (string) __('content.fields.body_format'),
            'cover_media_id' => (string) __('content.fields.cover_image'),
            'sort_order' => (string) __('content.fields.sort_order'),
            'fallback_to_ar' => (string) __('content.fields.fallback_to_ar'),
        ];
    }
}
