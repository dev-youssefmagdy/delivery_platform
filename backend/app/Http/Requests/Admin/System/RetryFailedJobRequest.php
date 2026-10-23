<?php

namespace App\Http\Requests\Admin\System;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\FailedJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Manual retry of a failed job: a reason is mandatory.
 */
class RetryFailedJobRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        return Gate::allows('retry', FailedJob::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
