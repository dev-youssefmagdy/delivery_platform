<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerKind;
use App\Enums\Content\BannerPlacement;
use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentBanner;
use App\Rules\SafeContentUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveBannerRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $banner = $this->route('contentBanner');

        return $banner instanceof ContentBanner ? Gate::allows('update', $banner) : Gate::allows('create', ContentBanner::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(BannerKind::class)],
            'placement' => ['required', Rule::enum(BannerPlacement::class)],
            'audience' => ['nullable', Rule::enum(BannerAudience::class)],
            'media_id' => ['nullable', 'integer', 'exists:content_media,id'],
            'cta_url' => ['nullable', 'string', 'max:500', 'required_with:cta_label.ar', new SafeContentUrl],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'fallback_to_ar' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ]
        + $this->translated('title', 191, true)
        + $this->translated('body', 500)
        + $this->translated('cta_label', 60);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['title', 'body', 'cta_label'], 'content') + [
            'kind' => (string) __('admin.content.fields.banner_kind'),
            'placement' => (string) __('admin.content.fields.placement'),
            'audience' => (string) __('admin.content.fields.audience'),
            'media_id' => (string) __('admin.content.fields.image'),
            'cta_url' => (string) __('admin.content.fields.cta_url'),
            'starts_at' => (string) __('admin.content.fields.starts_at'),
            'ends_at' => (string) __('admin.content.fields.ends_at'),
            'sort_order' => (string) __('admin.common.sort_order'),
            'fallback_to_ar' => (string) __('admin.content.fields.fallback_to_ar'),
        ];
    }
}
