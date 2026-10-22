<?php

namespace App\Models;

use App\Enums\Notification\CampaignAudience;
use App\Enums\Notification\CampaignStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A marketing push sent to people who opted in (SRS §14.1). Content is stored per language; a person receives their own
 * language, Arabic when the English text is empty. Scheduled campaigns start from the scheduler.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property CampaignAudience $audience
 * @property string $title_ar
 * @property string $body_ar
 * @property string|null $title_en
 * @property string|null $body_en
 * @property CampaignStatus $status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int $recipients_count
 * @property int $skipped_count
 * @property int|null $created_by_admin_id
 * @property int|null $cancelled_by_admin_id
 * @property string|null $cancel_reason
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Admin|null $createdBy
 */
class PushCampaign extends Model
{
    use Auditable;
    use HasUuids;

    protected $fillable = [
        'name', 'audience', 'title_ar', 'body_ar', 'title_en', 'body_en', 'status', 'scheduled_at', 'started_at', 'finished_at',
        'recipients_count', 'skipped_count', 'created_by_admin_id', 'cancelled_by_admin_id', 'cancel_reason', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'draft', 'recipients_count' => 0, 'skipped_count' => 0, 'lock_version' => 0];

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

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id')->withTrashed();
    }

    /**
     * @return array{0: string, 1: string} title and body for a language (Arabic when the language has no text)
     */
    public function contentFor(string $locale): array
    {
        if ($locale === 'en' && $this->title_en !== null && $this->title_en !== '' && $this->body_en !== null && $this->body_en !== '') {
            return [$this->title_en, $this->body_en];
        }

        return [$this->title_ar, $this->body_ar];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => CampaignAudience::class,
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
