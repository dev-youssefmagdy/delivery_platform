<?php

namespace App\Models;

use App\Models\Contracts\TranslatedContent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * An image of the media library (public disk). The stored file was re-encoded on upload; `variants` maps a
 * width (320, 960, 1600) to the path of the resized copy.
 *
 * @property int $id
 * @property string $uuid
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property int $width
 * @property int $height
 * @property string|null $alt translated in the current locale; raw JSON {ar, en}
 * @property array<string, string>|null $variants
 * @property Carbon $created_at
 */
class ContentMedia extends Model implements TranslatedContent
{
    use Auditable;
    use HasTranslations;

    protected $table = 'content_media';

    /** @var list<string> */
    public array $translatable = ['alt'];

    /** @var list<string> */
    protected $fillable = ['uuid', 'disk', 'path', 'original_name', 'mime', 'size_bytes', 'width', 'height', 'alt', 'variants', 'uploaded_by_admin_id'];

    protected static function booted(): void
    {
        static::creating(static function (self $media): void {
            $media->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    public function url(?int $width = null): string
    {
        $path = $width !== null ? ($this->variants[(string) $width] ?? $this->path) : $this->path;

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
        ];
    }
}
