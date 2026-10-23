<?php

namespace App\Http\Requests\Website;

use App\DTOs\Lead\LeadSubmissionData;
use App\Http\Requests\Website\Concerns\GuardsPublicForm;
use App\Support\Auth\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Become a provider" lead form. Contact data and interest only: there is deliberately no file field (documents are collected
 * later, inside the provider app, over authenticated private storage).
 */
class ProviderLeadRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'email' => ['nullable', 'string', 'email:rfc', 'max:191'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('active', true)],
            'city_other' => ['nullable', 'string', 'max:120', 'required_without:city_id'],
            'service_category_id' => ['nullable', 'integer', Rule::exists('service_categories', 'id')->where('active', true)],
            'vehicle_note' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:1500'],
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
            'city_other.required_without' => (string) __('site.provider.errors.city'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('site.provider.fields.name'),
            'phone' => (string) __('site.provider.fields.phone'),
            'email' => (string) __('site.provider.fields.email'),
            'city_id' => (string) __('site.provider.fields.city'),
            'city_other' => (string) __('site.provider.fields.city_other'),
            'service_category_id' => (string) __('site.provider.fields.service'),
            'vehicle_note' => (string) __('site.provider.fields.vehicle'),
            'message' => (string) __('site.provider.fields.message'),
            'consent' => (string) __('site.forms.consent_label'),
        ];
    }

    public function data(): LeadSubmissionData
    {
        return new LeadSubmissionData(
            name: (string) $this->trimmed('name'),
            phoneE164: (string) $this->validated('phone'),
            email: $this->trimmed('email'),
            cityId: $this->filled('city_id') ? (int) $this->input('city_id') : null,
            cityOther: $this->trimmed('city_other'),
            serviceCategoryId: $this->filled('service_category_id') ? (int) $this->input('service_category_id') : null,
            vehicleNote: $this->trimmed('vehicle_note'),
            message: $this->trimmed('message'),
            locale: app()->getLocale(),
            ip: $this->ip(),
        );
    }
}
