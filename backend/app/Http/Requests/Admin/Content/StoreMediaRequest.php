<?php

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Concerns\ValidatesContent;
use App\Models\ContentMedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Image upload. The file is also verified by decoding it in ContentMediaService (the extension and MIME rules here are
 * the first, cheap line so errors show before submit).
 */
class StoreMediaRequest extends FormRequest
{
    use ValidatesContent;

    public function authorize(): bool
    {
        return Gate::allows('create', ContentMedia::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'image', 'mimes:'.implode(',', (array) config('content.media.mimes')), 'max:'.(int) config('content.media.max_kb'),
                'dimensions:min_width='.(int) config('content.media.min_dimension').',min_height='.(int) config('content.media.min_dimension').',max_width='.(int) config('content.media.max_dimension').',max_height='.(int) config('content.media.max_dimension'),
            ],
        ] + $this->translated('alt', 191);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedLabels(['alt'], 'content') + ['file' => (string) __('admin.content.fields.file')];
    }
}
