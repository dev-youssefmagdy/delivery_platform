<?php

namespace App\Models;

use App\Enums\Content\AppKind;
use App\Enums\Content\AppPlatform;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Store link of a mobile app (customer or driver) on a platform, used by the website and app-download blocks.
 *
 * @property int $id
 * @property AppKind $app
 * @property AppPlatform $platform
 * @property string $url
 * @property bool $active
 * @property int $sort_order
 * @property int $lock_version
 */
class ContentAppLink extends Model
{
    use Auditable;

    protected $table = 'content_app_links';

    /** @var list<string> */
    protected $fillable = ['app', 'platform', 'url', 'active', 'sort_order', 'lock_version'];

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
            'app' => AppKind::class,
            'platform' => AppPlatform::class,
            'active' => 'boolean',
            'sort_order' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
