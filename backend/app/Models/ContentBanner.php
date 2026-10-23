<?php

namespace App\Models;

use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerKind;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\ContentWorkflowStatus;
use App\Models\Concerns\HasContentWorkflow;
use App\Models\Contracts\TranslatedContent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A banner or announcement shown in an app or on the website placement.
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentBanner extends Model implements TranslatedContent
{
    use Auditable;
    use HasContentWorkflow;
    use HasTranslations;

    protected $table = 'content_banners';

    /** @var list<string> */
    public array $translatable = ['title', 'body', 'cta_label'];

    /** @var list<string> */
    protected $fillable = ['kind', 'placement', 'audience', 'title', 'body', 'media_id', 'cta_label', 'cta_url', 'starts_at', 'ends_at', 'sort_order', 'fallback_to_ar', ...self::WORKFLOW];

    /** @var list<string> */
    protected array $auditExcluded = ['body_html', 'answer_html'];

    public const WORKFLOW = ['uuid', 'status', 'scheduled_at', 'published_at', 'published_by_admin_id', 'created_by_admin_id', 'updated_by_admin_id', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
        'kind' => 'banner',
        'audience' => 'all',
        'fallback_to_ar' => true,
    ];

    /**
     * @return BelongsTo<ContentMedia, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class, 'media_id');
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
            'kind' => BannerKind::class,
            'placement' => BannerPlacement::class,
            'audience' => BannerAudience::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
            'fallback_to_ar' => 'boolean',
        ];
    }
}
