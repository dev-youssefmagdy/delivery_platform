<?php

namespace App\Http\Requests\Admin\Payout;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\PayoutRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Group every approved payout that is not in a batch yet (one currency, oldest first) into a transfer batch. The note is mandatory.
 */
class CreatePayoutBatchRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        return Gate::allows('createBatch', PayoutRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
