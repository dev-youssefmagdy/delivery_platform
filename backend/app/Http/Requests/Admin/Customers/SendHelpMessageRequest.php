<?php

namespace App\Http\Requests\Admin\Customers;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Send an in-app help message: subject, body and the (mandatory) reason it is sent for.
 */
class SendHelpMessageRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof User && Gate::allows('sendMessage', $customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true) + [
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
}
