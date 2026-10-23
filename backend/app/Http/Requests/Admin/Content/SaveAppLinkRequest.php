<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\AppKind;
use App\Enums\Content\AppPlatform;
use App\Models\ContentAppLink;
use App\Rules\SafeContentUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveAppLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $link = $this->route('contentAppLink');

        return $link instanceof ContentAppLink ? Gate::allows('update', $link) : Gate::allows('create', ContentAppLink::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $link = $this->route('contentAppLink');

        return [
            'app' => ['required', Rule::enum(AppKind::class)],
            'platform' => [
                'required', Rule::enum(AppPlatform::class),
                Rule::unique('content_app_links', 'platform')->where('app', $this->input('app'))->ignore($link instanceof ContentAppLink ? $link->id : null),
            ],
            'url' => ['required', 'string', 'max:500', new SafeContentUrl(allowRelative: false, httpsOnly: true)],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'app' => (string) __('content.fields.app'),
            'platform' => (string) __('content.fields.platform'),
            'url' => (string) __('content.fields.store_url'),
            'active' => (string) __('content.fields.active'),
            'sort_order' => (string) __('content.fields.sort_order'),
        ];
    }
}
