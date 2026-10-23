<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\ContentType;
use App\Http\Requests\Admin\Concerns\HasReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Copy an earlier revision back into the draft (content.manage, drafts only). The reason is optional.
 */
class RestoreRevisionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        $type = ContentType::tryFrom((string) $this->route('contentType'));
        $content = $type?->modelClass()::query()->find((int) $this->route('content'));

        return $content !== null && Gate::allows('update', $content);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules(false);
    }
}
