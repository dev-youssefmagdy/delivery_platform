<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HasReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Base of the small "do one thing to the model in the route" admin requests: authorizes `ability()` on the bound
 * route parameter through its policy (so the server refuses what the UI hides) and, for dangerous actions, requires a
 * reason. Subclasses name the ability and the route parameter.
 */
abstract class ModelActionRequest extends FormRequest
{
    use HasReason;

    abstract protected function ability(): string;

    protected function parameter(): string
    {
        return 'provider';
    }

    protected function reasonIsRequired(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        $model = $this->route($this->parameter());

        return $model instanceof Model && Gate::allows($this->ability(), $model);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // An optional reason (a note) is still validated so the service receives it.
        return $this->reasonRules($this->reasonIsRequired());
    }
}
