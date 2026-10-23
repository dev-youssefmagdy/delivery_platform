<?php

namespace App\Http\Requests\Admin\Growth;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\LoyaltyLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Activates or deactivates a loyalty level. Deactivating needs a reason.
 */
class ToggleLoyaltyLevelRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $level = $this->route('loyaltyLevel');

        return $level instanceof LoyaltyLevel && Gate::allows('toggle', $level);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['active' => ['required', 'boolean']] + $this->reasonRules(! $this->wantsActive());
    }

    public function wantsActive(): bool
    {
        return $this->boolean('active');
    }
}
