<?php

namespace App\Http\Requests\Admin\Customers;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Rejecting a deletion request needs a reason (kept with the request).
 */
class RejectAccountDeletionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof User && Gate::allows('processDeletion', $customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
