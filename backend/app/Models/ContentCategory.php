<?php

namespace App\Models;

use App\Enums\Content\CategoryKind;
use App\Models\Contracts\TranslatedContent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Groups FAQs or articles (help-center sections).
 *
 * @property int $id
 * @property CategoryKind $kind
 * @property string $slug
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale; raw JSON {ar, en}
 * @property int $sort_order
 * @property bool $active
 * @property int $lock_version
 */
class ContentCategory extends Model implements TranslatedContent
{
    use Auditable;
    use HasTranslations;

    protected $table = 'content_categories';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = ['kind', 'slug', 'name', 'description', 'sort_order', 'active', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['active' => true, 'sort_order' => 0, 'lock_version' => 0];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'kind' => CategoryKind::class,
            'sort_order' => 'integer',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
