<?php

namespace App\Models;

use App\Enums\Content\MenuLocation;
use App\Models\Concerns\HasContentWorkflow;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A website menu (header or footer column): a list of links.
 *
 * Rich text sources are stored as written; the sanitized HTML copy (`*_html`) is what is served. Translatable
 * columns hold {ar, en}: Arabic is required, English optional with a safe fallback to Arabic (`fallback_to_ar`).
 */
class ContentMenu extends Model
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
            ...self::workflowCasts(),
            'location' => MenuLocation::class,
            'items' => 'array',
            'fallback_to_ar' => 'boolean',
        ];
    }
}
