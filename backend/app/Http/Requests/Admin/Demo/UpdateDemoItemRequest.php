<?php

namespace App\Http\Requests\Admin\Demo;

use App\Models\DemoItem;
use Illuminate\Support\Facades\Gate;

class UpdateDemoItemRequest extends StoreDemoItemRequest
{
    public function authorize(): bool
    {
        $demoItem = $this->route('demoItem');

        return $demoItem instanceof DemoItem && Gate::allows('update', $demoItem);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
