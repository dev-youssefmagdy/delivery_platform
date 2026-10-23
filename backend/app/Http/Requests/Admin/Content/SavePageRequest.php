<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\BlockType;
use App\Enums\Content\BodyFormat;
use App\Enums\Content\PageKind;
use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentPage;
use App\Rules\SafeContentUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create / update of a page (homepage, generic, service or city page). The kind is chosen on create and fixed afterwards.
 */
class SavePageRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $page = $this->route('contentPage');

        return $page instanceof ContentPage ? Gate::allows('update', $page) : Gate::allows('create', ContentPage::class);
    }

    protected function prepareForValidation(): void
    {
        $page = $this->route('contentPage');

        if ($page instanceof ContentPage) {
            $this->merge(['kind' => $page->kind->value]);
        }

        if ($this->input('kind') === PageKind::Home->value) {
            $this->merge(['slug' => 'home']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $page = $this->route('contentPage');

        return [
            'kind' => ['required', Rule::enum(PageKind::class)],
            'slug' => ['required', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_pages', 'slug')->ignore($page instanceof ContentPage ? $page->id : null)],
            'body_format' => ['required', Rule::enum(BodyFormat::class)],
            'blocks' => ['nullable', 'array', 'max:30'],
            'blocks.*.type' => ['required', Rule::enum(BlockType::class)],
            'blocks.*.title_ar' => ['required', 'string', 'max:191'],
            'blocks.*.title_en' => ['nullable', 'string', 'max:191'],
            'blocks.*.body_ar' => ['nullable', 'string', 'max:5000'],
            'blocks.*.body_en' => ['nullable', 'string', 'max:5000'],
            'blocks.*.items_ar' => ['nullable', 'string', 'max:3000'],
            'blocks.*.items_en' => ['nullable', 'string', 'max:3000'],
            'blocks.*.cta_label_ar' => ['nullable', 'string', 'max:60'],
            'blocks.*.cta_label_en' => ['nullable', 'string', 'max:60'],
            'blocks.*.cta_url' => ['nullable', 'string', 'max:500', new SafeContentUrl(allowContact: true)],
            'blocks.*.ref' => ['nullable', 'string', 'max:96', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'hero_media_id' => ['nullable', 'integer', 'exists:content_media,id'],
            'service_category_id' => [Rule::requiredIf(fn (): bool => $this->input('kind') === PageKind::Service->value), 'nullable', 'integer', 'exists:service_categories,id'],
            'city_id' => [Rule::requiredIf(fn (): bool => $this->input('kind') === PageKind::City->value), 'nullable', 'integer', 'exists:cities,id'],
            'fallback_to_ar' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ]
        + $this->translated('title', 191, true)
        + $this->translated('summary', 500)
        + $this->translated('body', 100000)
        + $this->seoRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['title', 'summary', 'body'], 'content') + $this->seoLabels() + [
            'kind' => (string) __('admin.content.fields.kind'),
            'slug' => (string) __('admin.content.fields.slug'),
            'body_format' => (string) __('admin.content.fields.body_format'),
            'blocks' => (string) __('admin.content.fields.blocks'),
            'blocks.*.type' => (string) __('admin.content.blocks.type'),
            'blocks.*.title_ar' => (string) __('admin.content.blocks.title').' (ar)',
            'blocks.*.title_en' => (string) __('admin.content.blocks.title').' (en)',
            'blocks.*.body_ar' => (string) __('admin.content.blocks.body').' (ar)',
            'blocks.*.body_en' => (string) __('admin.content.blocks.body').' (en)',
            'blocks.*.items_ar' => (string) __('admin.content.blocks.items').' (ar)',
            'blocks.*.items_en' => (string) __('admin.content.blocks.items').' (en)',
            'blocks.*.cta_label_ar' => (string) __('admin.content.blocks.cta_label').' (ar)',
            'blocks.*.cta_label_en' => (string) __('admin.content.blocks.cta_label').' (en)',
            'blocks.*.cta_url' => (string) __('admin.content.blocks.cta_url'),
            'blocks.*.ref' => (string) __('admin.content.blocks.ref'),
            'hero_media_id' => (string) __('admin.content.fields.hero_image'),
            'service_category_id' => (string) __('admin.content.fields.service_category'),
            'city_id' => (string) __('admin.content.fields.city'),
            'fallback_to_ar' => (string) __('admin.content.fields.fallback_to_ar'),
        ];
    }
}
