<?php

namespace App\Http\Requests\Admin\Geography;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SuspendServiceZoneRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $zone = $this->route('serviceZone');

        return $zone instanceof ServiceZone && Gate::allows('suspend', $zone);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
