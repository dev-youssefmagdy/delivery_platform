<?php

namespace App\Models;

use App\Enums\Content\BodyFormat;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\PageKind;
use App\Models\Concerns\HasContentWorkflow;
use App\Models\Contracts\TranslatedContent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A page: homepage (blocks), generic page, service page (linked to a service category) or city page (linked to a city).
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentPage extends Model implements TranslatedContent
{
    use Auditable;
    use HasContentWorkflow;
    use HasTranslations;

    protected $table = 'content_pages';

    /** @var list<string> */
    public array $translatable = ['title', 'summary', 'body', 'body_html', 'seo_title', 'seo_description', 'og_title', 'og_description'];

    /** @var list<string> */
    protected $fillable = ['kind', 'slug', 'title', 'summary', 'body_format', 'body', 'body_html', 'blocks', 'hero_media_id', 'service_category_id', 'city_id', 'fallback_to_ar', 'seo_title', 'seo_description', 'og_title', 'og_description', 'og_image_id', 'canonical_url', 'noindex', ...self::WORKFLOW];

    /** @var list<string> */
    protected array $auditExcluded = ['body_html', 'answer_html'];

    public const WORKFLOW = ['uuid', 'status', 'scheduled_at', 'published_at', 'published_by_admin_id', 'created_by_admin_id', 'updated_by_admin_id', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
        'kind' => 'generic',
        'fallback_to_ar' => true,
    ];

    /**
     * @return BelongsTo<ContentMedia, $this>
     */
    public function hero(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class, 'hero_media_id');
    }

    /**
     * @return BelongsTo<ContentMedia, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class, 'og_image_id');
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
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
            'kind' => PageKind::class,
            'body_format' => BodyFormat::class,
            'blocks' => 'array',
            'fallback_to_ar' => 'boolean',
            'noindex' => 'boolean',
        ];
    }
}
