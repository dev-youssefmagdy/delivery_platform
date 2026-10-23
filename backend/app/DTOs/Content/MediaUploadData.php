<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;
use App\Http\Requests\Admin\Content\StoreMediaRequest;
use Illuminate\Http\UploadedFile;

final readonly class MediaUploadData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $alt
     */
    public function __construct(public UploadedFile $file, public array $alt) {}

    public static function fromRequest(StoreMediaRequest $request): self
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        return new self($file, self::translations($request->validated('alt') ?? []));
    }

    /**
     * @return array<string, string>
     */
    public function altTranslations(): array
    {
        return self::filled($this->alt);
    }
}
