<?php

namespace App\Models;

use App\Enums\Support\TicketCategory;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A canned reply agents insert into a ticket answer, written in Arabic and optionally in English (SRS §16.11).
 *
 * @property int $id
 * @property string $uuid
 * @property string $title_ar
 * @property string|null $title_en
 * @property string $body_ar
 * @property string|null $body_en
 * @property TicketCategory|null $category
 * @property bool $active
 * @property int $lock_version
 * @property int|null $created_by_admin_id
 * @property Carbon $created_at
 */
class SupportMacro extends Model
{
    use Auditable;
    use HasUuids;

    protected $fillable = ['title_ar', 'title_en', 'body_ar', 'body_en', 'category', 'active', 'lock_version', 'created_by_admin_id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['active' => true, 'lock_version' => 0];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function titleFor(string $locale): string
    {
        return $locale === 'en' && $this->title_en !== null && $this->title_en !== '' ? $this->title_en : $this->title_ar;
    }

    public function bodyFor(string $locale): string
    {
        return $locale === 'en' && $this->body_en !== null && $this->body_en !== '' ? $this->body_en : $this->body_ar;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['category' => TicketCategory::class, 'active' => 'boolean'];
    }
}
