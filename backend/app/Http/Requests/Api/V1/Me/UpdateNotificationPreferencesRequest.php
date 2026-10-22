<?php

namespace App\Http\Requests\Api\V1\Me;

use App\DTOs\Notification\NotificationPreferenceUpdate;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT /me/notification-preferences: a list of `{category, channel, enabled}` changes. Unlisted pairs keep their value.
 */
class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1', 'max:64'],
            'preferences.*.category' => ['required', 'string', Rule::in(array_map(static fn (NotificationCategory $case): string => $case->value, NotificationCategory::forUsers()))],
            'preferences.*.channel' => ['required', 'string', Rule::in(array_map(static fn (NotificationChannel $case): string => $case->value, NotificationChannel::preferenceChannels()))],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<NotificationPreferenceUpdate>
     */
    public function toDtos(): array
    {
        return array_map(static fn (array $row): NotificationPreferenceUpdate => new NotificationPreferenceUpdate(
            NotificationCategory::from((string) $row['category']),
            NotificationChannel::from((string) $row['channel']),
            filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN),
        ), array_values((array) $this->validated('preferences')));
    }
}
