<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Provider\VehiclePhotoData;
use App\Enums\Provider\VehiclePhotoKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * POST /provider/vehicles/{vehicle}/photos (multipart: `photo`, `kind`).
 */
class StoreVehiclePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'max:'.(int) config('provider.vehicles.photo_max_kilobytes', 8192)],
            'kind' => ['required', Rule::enum(VehiclePhotoKind::class)],
        ];
    }

    public function toDto(): VehiclePhotoData
    {
        $file = $this->file('photo');

        return new VehiclePhotoData(
            $file instanceof UploadedFile ? $file : throw new LogicException('The photo is validated as a file.'),
            VehiclePhotoKind::from((string) $this->validated('kind')),
        );
    }
}
