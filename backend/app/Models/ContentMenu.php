<?php

namespace App\Models;

use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\MenuLocation;
use App\Models\Concerns\HasContentWorkflow;
use App\Models\Contracts\TranslatedContent;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A website menu (header or footer column): a list of links.
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentMenu extends Model implements TranslatedContent
{
    use Auditable;
    use HasContentWorkflow;
    use HasTranslations;

    protected $table = 'content_menus';

    /** @var list<string> */
    public array $translatable = ['title'];

    /** @var list<string> */
    protected $fillable = ['location', 'title', 'items', 'fallback_to_ar', ...self::WORKFLOW];

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
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentWorkflowStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'lock_version' => 'integer',
            'location' => MenuLocation::class,
            'items' => 'array',
            'fallback_to_ar' => 'boolean',
        ];
    }
}
