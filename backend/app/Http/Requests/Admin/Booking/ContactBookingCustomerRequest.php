<?php

namespace App\Http\Requests\Admin\Booking;

use App\DTOs\Customers\HelpMessageData;

/**
 * No-offer queue: send the booking's customer an in-app message (subject, body, reason for the audit log).
 */
class ContactBookingCustomerRequest extends BookingOverrideRequest
{
    protected function ability(): string
    {
        return 'manage';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'subject' => ['required', 'string', 'min:3', 'max:191'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => (string) __('admin.common.reason'),
            'subject' => (string) __('admin.customers.fields.message_subject'),
            'body' => (string) __('admin.customers.fields.message_body'),
        ];
    }

    public function toMessage(): HelpMessageData
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new HelpMessageData(trim((string) $data['subject']), trim((string) $data['body']), (string) $this->reason());
    }
}
