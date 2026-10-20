<?php

namespace App\Http\Requests\Admin\Geography;

use App\Models\ServiceZone;
use App\Support\Geo\GeoPolygon;
use App\Support\Geo\InvalidCoordinatesException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * POST /service-zones/overlaps: live overlap warning while drawing.
 */
class CheckZoneOverlapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ServiceZone::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'polygon' => ['required', 'string', 'max:100000'],
            'zone_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function polygon(): ?GeoPolygon
    {
        try {
            return GeoPolygon::fromLatLngList($this->validated('polygon'));
        } catch (InvalidCoordinatesException) {
            return null;
        }
    }

    public function zoneId(): ?int
    {
        $id = $this->validated('zone_id');

        return $id === null ? null : (int) $id;
    }
}
