<?php

namespace App\Http\Requests\Website;

use App\DTOs\Support\WebsiteContactData;
use App\Enums\Support\TicketCategory;
use App\Http\Requests\Website\Concerns\GuardsPublicForm;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public contact form: creates a support ticket and shows its reference number.
 */
class ContactRequest extends FormRequest
{
    use GuardsPublicForm;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->guardRules() + [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'regex:'.PhoneNumber::PATTERN],
            'category' => ['required', Rule::enum(TicketCategory::class)->only(TicketCategory::forWebsite())],
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->guardMessages() + [
            'phone.regex' => (string) __('site.forms.errors.phone'),
            'consent.accepted' => (string) __('site.forms.errors.consent'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('site.contact.fields.name'),
            'email' => (string) __('site.contact.fields.email'),
            'phone' => (string) __('site.contact.fields.phone'),
            'category' => (string) __('site.contact.fields.category'),
            'subject' => (string) __('site.contact.fields.subject'),
            'message' => (string) __('site.contact.fields.message'),
            'consent' => (string) __('site.forms.consent_label'),
        ];
    }

    public function toData(): WebsiteContactData
    {
        return new WebsiteContactData(
            name: (string) $this->trimmed('name'),
            email: mb_strtolower((string) $this->trimmed('email')),
            phone: $this->trimmed('phone'),
            category: TicketCategory::from((string) $this->input('category')),
            subject: (string) $this->trimmed('subject'),
            message: (string) $this->trimmed('message'),
            locale: app()->getLocale(),
            ip: $this->ip(),
        );
    }
}
