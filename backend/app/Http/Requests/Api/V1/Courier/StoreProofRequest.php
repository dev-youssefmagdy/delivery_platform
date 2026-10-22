<?php

namespace App\Http\Requests\Api\V1\Courier;

use App\DTOs\Courier\ProofUploadData;
use App\Enums\Catalog\ProofType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /provider/bookings/{booking}/proofs (multipart for pictures, JSON otherwise). Shape checks only: the service decides
 * the stage, inspects and scans the picture and enforces the booking rules.
 */
class StoreProofRequest extends FormRequest
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
        $type = (string) $this->input('type');

        return [
            'type' => ['required', 'string', Rule::enum(ProofType::class)],
            'file' => [Rule::requiredIf(in_array($type, [ProofType::Photo->value, ProofType::Signature->value], true)), 'nullable', 'file', 'max:'.(int) config('courier.proofs.max_kilobytes', 5120)],
            'items' => [Rule::requiredIf($type === ProofType::Checklist->value), 'nullable', 'array', 'min:1', 'max:20'],
            'items.*.code' => ['required_with:items', 'string', 'max:40'],
            'items.*.checked' => ['required_with:items', 'boolean'],
            'weight_kg' => [Rule::requiredIf($type === ProofType::Weight->value), 'nullable', 'numeric', 'gt:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function toDto(): ProofUploadData
    {
        return new ProofUploadData(
            type: ProofType::from((string) $this->input('type')),
            file: $this->file('file'),
            checklist: array_values(array_map(
                static fn (array $item): array => ['code' => (string) $item['code'], 'checked' => filter_var($item['checked'], FILTER_VALIDATE_BOOLEAN)],
                (array) $this->input('items', []),
            )),
            weightKg: $this->filled('weight_kg') ? (string) $this->input('weight_kg') : null,
            note: $this->filled('note') ? (string) $this->input('note') : null,
        );
    }
}
