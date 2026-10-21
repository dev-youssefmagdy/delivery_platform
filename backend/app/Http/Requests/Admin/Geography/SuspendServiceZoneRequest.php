<?php

namespace App\Http\Requests\Admin\Geography;

use App\Http\Requests\Admin\Concerns\HasReason;
use App\Models\ServiceZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Dangerous: the reason is mandatory; `confirm_active_bookings` acknowledges bookings in progress in the zone (the
 * service answers `zone_has_active_bookings` when it is missing and such bookings exist).
 */
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
        return $this->reasonRules(true) + ['confirm_active_bookings' => ['nullable', 'boolean']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'confirm_active_bookings' => (string) __('admin.service_zones.fields.confirm_active_bookings')];
    }
}
