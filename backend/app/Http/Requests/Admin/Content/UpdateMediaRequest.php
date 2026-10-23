<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateMediaRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        $media = $this->route('contentMedia');

        return $media instanceof ContentMedia && Gate::allows('update', $media);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->translated('alt', 191);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['alt'], 'content');
    }
}
