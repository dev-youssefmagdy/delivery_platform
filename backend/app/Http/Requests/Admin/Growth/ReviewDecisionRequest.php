<?php

namespace App\Http\Requests\Admin\Growth;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\IncentiveProgress;
use App\Models\ReferralRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Decision on a flagged referral or a held quest progress. Approving / releasing may carry a note; rejecting needs a reason.
 */
class ReviewDecisionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $subject = $this->route('referralRelationship') ?? $this->route('incentiveProgress');

        return ($subject instanceof ReferralRelationship || $subject instanceof IncentiveProgress) && Gate::allows('review', $subject);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(str_ends_with((string) $this->route()?->getName(), 'reject'));
    }
}
