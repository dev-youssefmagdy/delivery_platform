<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Rules\SafeContentUrl;

/**
 * Rule builders shared by the CMS form requests: ar/en text (Arabic required where stated), SEO fields, safe URLs.
 *
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait ValidatesContent
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function translated(string $field, int $max, bool $arabicRequired = false): array
    {
        return [
            $field => [$arabicRequired ? 'required' : 'nullable', 'array'],
            $field.'.ar' => [$arabicRequired ? 'required' : 'nullable', 'string', 'max:'.$max],
            $field.'.en' => ['nullable', 'string', 'max:'.$max],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function seoRules(): array
    {
        return $this->translated('seo_title', 191)
            + $this->translated('seo_description', 320)
            + $this->translated('og_title', 191)
            + $this->translated('og_description', 320)
            + [
                'og_image_id' => ['nullable', 'integer', 'exists:content_media,id'],
                'canonical_url' => ['nullable', 'string', 'max:500', new SafeContentUrl(allowRelative: false, httpsOnly: true)],
                'noindex' => ['nullable', 'boolean'],
            ];
    }

    /**
     * @param list<string> $fields
     * @return array<string, string>
     */
    protected function translatedLabels(array $fields, string $group): array
    {
        $labels = [];

        foreach ($fields as $field) {
            $label = (string) __("admin.{$group}.fields.{$field}");
            $labels[$field] = $label;
            $labels[$field.'.ar'] = $label.' ('.\App\Enums\Common\Locale::Arabic->label().')';
            $labels[$field.'.en'] = $label.' ('.\App\Enums\Common\Locale::English->label().')';
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    protected function seoLabels(): array
    {
        return $this->translatedLabels(['seo_title', 'seo_description', 'og_title', 'og_description'], 'content') + [
            'og_image_id' => (string) __('content.fields.og_image'),
            'canonical_url' => (string) __('content.fields.canonical_url'),
            'noindex' => (string) __('content.fields.noindex'),
        ];
    }
}
