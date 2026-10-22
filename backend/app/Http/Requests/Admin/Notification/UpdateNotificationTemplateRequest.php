<?php

namespace App\Http\Requests\Admin\Notification;

use App\DTOs\Notification\NotificationTemplateData;
use App\Enums\Notification\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Edit the text of a template. Unknown placeholders are refused by the service with a message naming them.
 */
class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('notificationTemplate');

        return $template instanceof NotificationTemplate && Gate::allows('update', $template);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('notificationTemplate');
        $needsTitle = ! $template instanceof NotificationTemplate || $template->channel !== NotificationChannel::Sms;

        return [
            'title' => ['nullable', 'string', 'max:160', Rule::requiredIf($needsTitle)],
            'body' => ['required', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => (string) __('admin.notification_templates.fields.title'),
            'body' => (string) __('admin.notification_templates.fields.body'),
        ];
    }

    public function toDto(): NotificationTemplateData
    {
        return NotificationTemplateData::fromRequest($this);
    }
}
