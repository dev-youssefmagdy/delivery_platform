<?php

namespace App\Models;

use App\Enums\System\AppPlatform;
use App\Enums\System\AppTarget;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Minimum version, force-update switch and store link of one app on one platform (SRS 16.15).
 *
 * @property int $id
 * @property AppTarget $app
 * @property AppPlatform $platform
 * @property string $min_version
 * @property string|null $latest_version
 * @property bool $force_update
 * @property string|null $store_url
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AppVersion extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = ['app', 'platform', 'min_version', 'latest_version', 'force_update', 'store_url', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['min_version' => '1.0.0', 'force_update' => false, 'lock_version' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['app' => AppTarget::class, 'platform' => AppPlatform::class, 'force_update' => 'boolean'];
    }
}
