<?php

namespace App\Http\Requests\Admin\Notification;

use App\DTOs\Notification\PushCampaignData;
use App\Enums\Notification\CampaignAudience;
use App\Models\PushCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a push campaign (draft). Arabic text is required, English is optional but complete or empty.
 */
class StorePushCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PushCampaign::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'audience' => ['required', 'string', Rule::enum(CampaignAudience::class)],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:65'],
            'title.en' => ['nullable', 'string', 'max:65', 'required_with:body.en'],
            'body' => ['required', 'array'],
            'body.ar' => ['required', 'string', 'max:178'],
            'body.en' => ['nullable', 'string', 'max:178', 'required_with:title.en'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('admin.push_campaigns.fields.name'),
            'audience' => (string) __('admin.push_campaigns.fields.audience'),
            'title.ar' => (string) __('admin.push_campaigns.fields.title').' (ar)',
            'title.en' => (string) __('admin.push_campaigns.fields.title').' (en)',
            'body.ar' => (string) __('admin.push_campaigns.fields.body').' (ar)',
            'body.en' => (string) __('admin.push_campaigns.fields.body').' (en)',
            'scheduled_at' => (string) __('admin.push_campaigns.fields.scheduled_at'),
        ];
    }

    public function toDto(): PushCampaignData
    {
        return PushCampaignData::fromRequest($this);
    }
}
