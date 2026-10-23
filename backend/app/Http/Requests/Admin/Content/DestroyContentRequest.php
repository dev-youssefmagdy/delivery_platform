<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Concerns\HasReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Deleting content or an asset: authorized by the policy of the bound model, reason required.
 */
class DestroyContentRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $model = collect(['contentPage', 'contentArticle', 'contentFaq', 'contentBanner', 'contentMenu', 'contentCategory', 'contentAppLink', 'contentMedia'])
            ->map(fn (string $name): mixed => $this->route($name))
            ->first(fn (mixed $value): bool => $value instanceof \Illuminate\Database\Eloquent\Model);

        return $model !== null && Gate::allows('delete', $model);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(true);
    }
}
