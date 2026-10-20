<?php

namespace App\Http\Requests\Admin\Geography;

use App\Models\ServiceZone;
use Illuminate\Support\Facades\Gate;

class UpdateServiceZoneRequest extends StoreServiceZoneRequest
{
    public function authorize(): bool
    {
        $zone = $this->route('serviceZone');

        return $zone instanceof ServiceZone && Gate::allows('update', $zone);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + ['lock_version' => ['required', 'integer', 'min:0']];
    }
}
