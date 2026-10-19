<?php

namespace App\Models;

use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use App\Support\Audit\Auditable;
use Database\Factories\PolicyVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * A version of a legal/policy document (terms, privacy …). Drafts are editable; publishing assigns the next
 * version number of the type, archives the previous published version and makes the row immutable.
 *
 * @property int $id
 * @property string $uuid
 * @property PolicyType $type
 * @property int|null $version
 * @property string $title translated in the current locale; raw JSON {ar, en}
 * @property string $content translated in the current locale; raw JSON {ar, en}
 * @property ContentStatus $status
 * @property string|null $change_note
 * @property Carbon|null $published_at
 * @property int|null $published_by_admin_id
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Admin|null $publisher
 */
class PolicyVersion extends Model
{
    use Auditable;

    /** @use HasFactory<PolicyVersionFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'content'];

    protected $fillable = ['uuid', 'type', 'version', 'title', 'content', 'status', 'change_note', 'published_at', 'published_by_admin_id', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $policy): void {
            $policy->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ContentStatus::Draft;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PolicyType::class,
            'status' => ContentStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
