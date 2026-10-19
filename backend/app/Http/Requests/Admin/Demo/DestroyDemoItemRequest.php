<?php

namespace App\Http\Requests\Admin\Demo;

use App\Models\DemoItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDemoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demoItem = $this->route('demoItem');

        return $demoItem instanceof DemoItem && Gate::allows('delete', $demoItem);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'min:'.config('admin.reason.min'), 'max:'.config('admin.reason.max')],
        ];
    }

    public function reason(): ?string
    {
        $reason = $this->validated('reason');

        return is_string($reason) && $reason !== '' ? $reason : null;
    }
}
