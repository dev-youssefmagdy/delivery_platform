<?php

namespace App\Http\Requests\Admin\Notification;

use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Models\NotificationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The preview panel of a template page: renders the saved text of every language with sample variables the editor can
 * change. Read-only, nothing is stored.
 */
class PreviewNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', NotificationTemplate::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_key' => ['required', 'string', Rule::enum(NotificationEvent::class)],
            'channel' => ['required', 'string', Rule::enum(NotificationChannel::class)],
            'variables' => ['nullable', 'array', 'max:12'],
            'variables.*' => ['nullable', 'string', 'max:120'],
        ];
    }

}
