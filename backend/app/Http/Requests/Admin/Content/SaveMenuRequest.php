<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\MenuLocation;
use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentMenu;
use App\Rules\SafeContentUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveMenuRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $menu = $this->route('contentMenu');

        return $menu instanceof ContentMenu ? Gate::allows('update', $menu) : Gate::allows('create', ContentMenu::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', Rule::enum(MenuLocation::class)],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.label_ar' => ['required', 'string', 'max:80'],
            'items.*.label_en' => ['nullable', 'string', 'max:80'],
            'items.*.url' => ['required', 'string', 'max:500', new SafeContentUrl(allowContact: true)],
            'items.*.new_tab' => ['nullable', 'boolean'],
            'fallback_to_ar' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ] + $this->translated('title', 191, true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['title'], 'content') + [
            'location' => (string) __('content.fields.location'),
            'items' => (string) __('content.fields.items'),
            'items.*.label_ar' => (string) __('content.fields.item_label').' (ar)',
            'items.*.label_en' => (string) __('content.fields.item_label').' (en)',
            'items.*.url' => (string) __('content.fields.item_url'),
            'fallback_to_ar' => (string) __('content.fields.fallback_to_ar'),
        ];
    }
}
