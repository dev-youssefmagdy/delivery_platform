<?php

namespace App\Http\Requests\Api\V1\Content;

use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannersRequest extends FormRequest
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
            'placement' => ['nullable', Rule::enum(BannerPlacement::class)],
            'audience' => ['nullable', Rule::enum(BannerAudience::class)],
        ];
    }

    /**
     * @return list<BannerPlacement>
     */
    public function placements(): array
    {
        $placement = BannerPlacement::tryFrom((string) $this->validated('placement'));

        return $placement === null ? [] : [$placement];
    }

    public function audience(): ?BannerAudience
    {
        return BannerAudience::tryFrom((string) $this->validated('audience'));
    }
}
