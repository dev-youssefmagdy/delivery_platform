<?php

namespace App\Http\Requests\Admin\Notification;

use App\DTOs\Notification\NotificationTemplateData;
use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Models\NotificationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create a template for an event, channel and language that has none yet.
 */
class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', NotificationTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_key' => ['required', 'string', Rule::enum(NotificationEvent::class)],
            'channel' => ['required', 'string', Rule::enum(NotificationChannel::class)],
            'locale' => ['required', 'string', Rule::enum(Locale::class), Rule::unique('notification_templates', 'locale')->where(fn ($query) => $query->where('event_key', $this->input('event_key'))->where('channel', $this->input('channel')))],
            'title' => ['nullable', 'string', 'max:160', Rule::requiredIf(fn (): bool => $this->input('channel') !== NotificationChannel::Sms->value)],
            'body' => ['required', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'event_key' => (string) __('admin.notification_templates.fields.event'),
            'channel' => (string) __('admin.notification_templates.fields.channel'),
            'locale' => (string) __('admin.notification_templates.fields.locale'),
            'title' => (string) __('admin.notification_templates.fields.title'),
            'body' => (string) __('admin.notification_templates.fields.body'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['locale.unique' => (string) __('admin.notification_templates.exists')];
    }

    public function toDto(): NotificationTemplateData
    {
        return NotificationTemplateData::fromRequest($this);
    }
}
