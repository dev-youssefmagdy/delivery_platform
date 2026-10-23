<?php

namespace App\Models;

use App\Enums\Content\ContentWorkflowStatus;
use App\Models\Concerns\HasContentWorkflow;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A question and answer, optionally grouped in a category and tied to a service.
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentFaq extends Model
{
    use Auditable;
    use HasContentWorkflow;
    use HasTranslations;

    protected $table = 'content_faqs';

    /** @var list<string> */
    public array $translatable = ['question', 'answer', 'answer_html'];

    /** @var list<string> */
    protected $fillable = ['category_id', 'service_category_id', 'question', 'answer', 'answer_html', 'sort_order', 'fallback_to_ar', ...self::WORKFLOW];

    /** @var list<string> */
    protected array $auditExcluded = ['body_html', 'answer_html'];

    public const WORKFLOW = ['uuid', 'status', 'scheduled_at', 'published_at', 'published_by_admin_id', 'created_by_admin_id', 'updated_by_admin_id', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
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
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
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
            'sort_order' => 'integer',
            'fallback_to_ar' => 'boolean',
        ];
    }
}
