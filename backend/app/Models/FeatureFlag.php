<?php

namespace App\Models;

use App\Enums\System\FeatureFlagKey;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A feature flag row (key from FeatureFlagKey) with its targeting: apps, cities, percentage rollout.
 * An empty `apps` / `city_ids` list means "everyone".
 *
 * @property int $id
 * @property FeatureFlagKey $key
 * @property bool $enabled
 * @property list<string>|null $apps
 * @property list<int>|null $city_ids
 * @property int $rollout_percent
 * @property string|null $note
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class FeatureFlag extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = ['key', 'enabled', 'apps', 'city_ids', 'rollout_percent', 'note', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['enabled' => false, 'rollout_percent' => 100, 'lock_version' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['key' => FeatureFlagKey::class, 'enabled' => 'boolean', 'apps' => 'array', 'city_ids' => 'array', 'rollout_percent' => 'integer'];
    }
}
