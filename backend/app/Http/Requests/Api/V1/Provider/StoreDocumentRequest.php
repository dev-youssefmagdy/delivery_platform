<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Provider\DocumentUploadData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use LogicException;

/**
 * POST /provider/documents (multipart). The type's own rules (allowed MIME types, size, expiry, number) are enforced by
 * `DocumentService`; this request validates the shape and the hard upload cap.
 */
class StoreDocumentRequest extends FormRequest
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
            'document_type' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'file' => ['required', 'file', 'max:'.(int) config('provider.documents.max_kilobytes', 10240)],
            'number' => ['nullable', 'string', 'min:3', 'max:40'],
            'issued_at' => ['nullable', 'date_format:Y-m-d'],
            'expires_at' => ['nullable', 'date_format:Y-m-d'],
            'vehicle_id' => ['nullable', 'uuid'],
        ];
    }

    public function toDto(): DocumentUploadData
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $file = $this->file('file');
        $date = static fn (mixed $value): ?CarbonImmutable => is_string($value) && $value !== '' ? (CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null) : null;

        return new DocumentUploadData(
            file: $file instanceof UploadedFile ? $file : throw new LogicException('The file is validated as a file.'),
            documentTypeCode: (string) $data['document_type'],
            number: isset($data['number']) && $data['number'] !== '' ? (string) $data['number'] : null,
            issuedAt: $date($data['issued_at'] ?? null),
            expiresAt: $date($data['expires_at'] ?? null),
            vehicleUuid: isset($data['vehicle_id']) && $data['vehicle_id'] !== '' ? (string) $data['vehicle_id'] : null,
        );
    }
}
