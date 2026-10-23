<?php

namespace App\Http\Requests\Admin\Growth;

use App\Models\LoyaltyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Create / update of a loyalty level.
 */
class SaveLoyaltyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $level = $this->route('loyaltyLevel');

        return $level instanceof LoyaltyLevel ? Gate::allows('update', $level) : Gate::allows('create', LoyaltyLevel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'name_en' => ['nullable', 'string', 'max:80'],
            'min_completed_jobs' => ['required', 'integer', 'min:1', 'max:100000'],
            'reward_minor' => ['required', 'integer', 'min:0', 'max:100000000'],
            'credit_valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'sort' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => (string) __('growth.fields.name'),
            'name_en' => (string) __('growth.fields.name_en'),
            'min_completed_jobs' => (string) __('growth.loyalty.fields.min_jobs'),
            'reward_minor' => (string) __('growth.loyalty.fields.reward'),
            'credit_valid_days' => (string) __('growth.loyalty.fields.valid_days'),
        ];
    }
}
