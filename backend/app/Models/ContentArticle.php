<?php

namespace App\Models;

use App\Enums\Content\ArticleType;
use App\Enums\Content\BodyFormat;
use App\Enums\Content\ContentWorkflowStatus;
use App\Models\Concerns\HasContentWorkflow;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A help-center or blog article.
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentArticle extends Model
{
    use Auditable;
    use HasContentWorkflow;
    use HasTranslations;

    protected $table = 'content_articles';

    /** @var list<string> */
    public array $translatable = ['title', 'summary', 'body', 'body_html', 'seo_title', 'seo_description', 'og_title', 'og_description'];

    /** @var list<string> */
    protected $fillable = ['type', 'slug', 'category_id', 'title', 'summary', 'body_format', 'body', 'body_html', 'cover_media_id', 'sort_order', 'fallback_to_ar', 'seo_title', 'seo_description', 'og_title', 'og_description', 'og_image_id', 'canonical_url', 'noindex', ...self::WORKFLOW];

    /** @var list<string> */
    protected array $auditExcluded = ['body_html', 'answer_html'];

    public const WORKFLOW = ['uuid', 'status', 'scheduled_at', 'published_at', 'published_by_admin_id', 'created_by_admin_id', 'updated_by_admin_id', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
        'type' => 'help',
        'fallback_to_ar' => true,
    ];

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<ContentMedia, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class, 'cover_media_id');
    }

    /**
     * @return BelongsTo<ContentMedia, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class, 'og_image_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentWorkflowStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'lock_version' => 'integer',
            'type' => ArticleType::class,
            'body_format' => BodyFormat::class,
            'sort_order' => 'integer',
            'fallback_to_ar' => 'boolean',
            'noindex' => 'boolean',
        ];
    }
}
