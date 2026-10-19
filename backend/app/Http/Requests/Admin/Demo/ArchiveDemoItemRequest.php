<?php

namespace App\Http\Requests\Admin\Demo;

use App\Models\DemoItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Dangerous action: the reason is mandatory (confirm dialog reason textarea).
 */
class ArchiveDemoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demoItem = $this->route('demoItem');

        return $demoItem instanceof DemoItem && Gate::allows('archive', $demoItem);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:'.config('admin.reason.min'), 'max:'.config('admin.reason.max')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason')];
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
