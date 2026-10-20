<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Models\ServiceFamily;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class UpdateServiceFamilyRequest extends StoreServiceFamilyRequest
{
    public function authorize(): bool
    {
        $family = $this->route('serviceFamily');

        return $family instanceof ServiceFamily && Gate::allows('update', $family);
    }

    /**
     * The code is immutable, so it is neither submitted nor validated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['code']) + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
