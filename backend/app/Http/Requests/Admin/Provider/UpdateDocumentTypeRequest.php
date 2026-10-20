<?php

namespace App\Http\Requests\Admin\Provider;

use App\Models\DocumentType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateDocumentTypeRequest extends StoreDocumentTypeRequest
{
    public function authorize(): bool
    {
        $type = $this->route('documentType');

        return $type instanceof DocumentType && Gate::allows('update', $type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
