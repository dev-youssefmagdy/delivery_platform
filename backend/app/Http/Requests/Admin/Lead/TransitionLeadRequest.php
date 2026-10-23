<?php

namespace App\Http\Requests\Admin\Lead;

use App\Enums\Lead\LeadStatus;
use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moves a lead to another status. The target must be legal from the current status (checked here and again under the row
 * lock in the service); rejecting, marking spam and reopening need a reason.
 */
class TransitionLeadRequest extends FormRequest
{
    use HasReason {
        attributes as reasonAttributes;
    }

    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && Gate::allows('transition', [$lead, $this->target()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->target();

        return [
            'status' => ['required', Rule::enum(LeadStatus::class)],
        ] + $this->reasonRules($target?->needsReason() ?? false);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->reasonAttributes() + ['status' => (string) __('leads.fields.status')];
    }

    public function target(): ?LeadStatus
    {
        return LeadStatus::tryFrom((string) $this->input('status'));
    }
}
