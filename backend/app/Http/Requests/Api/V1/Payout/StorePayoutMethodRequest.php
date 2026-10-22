<?php

namespace App\Http\Requests\Api\V1\Payout;

use App\DTOs\Payout\PayoutMethodData;
use App\Enums\Payout\PayoutMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST / PATCH /provider/payout-methods: a type, a label and the type's details (holder, bank / wallet and the account or wallet
 * number). Bank details are never echoed back; they are encrypted at rest.
 */
class StorePayoutMethodRequest extends FormRequest
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
        $type = PayoutMethodType::tryFrom((string) $this->input('type'));
        $required = $type?->requiredFields() ?? [];
        $details = [];

        foreach (['holder_name' => 120, 'bank_name' => 120, 'account_number' => 40, 'wallet_number' => 24, 'iban' => 40, 'branch' => 120] as $field => $max) {
            $details['details.'.$field] = [in_array($field, $required, true) ? 'required' : 'nullable', 'string', 'max:'.$max];
        }

        $details['details.account_number'][] = 'regex:/^[A-Za-z0-9 \-]{4,40}$/';
        $details['details.wallet_number'][] = 'regex:/^\+?[0-9 \-]{8,24}$/';

        return [
            'type' => ['required', Rule::enum(PayoutMethodType::class)],
            'label' => ['required', 'string', 'min:2', 'max:80'],
            'details' => ['required', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ] + $details;
    }

    public function toDto(): PayoutMethodData
    {
        /** @var array<string, string> $details */
        $details = collect((array) $this->validated('details'))->filter(static fn (mixed $value): bool => is_string($value))->all();

        return new PayoutMethodData(
            type: PayoutMethodType::from((string) $this->validated('type')),
            label: trim((string) $this->validated('label')),
            details: $details,
            makeDefault: $this->boolean('is_default'),
        );
    }
}
