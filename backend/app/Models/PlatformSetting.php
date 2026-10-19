<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One persisted override of a SettingKey (ADR-10). Read through SettingsService only; the value is typed by
 * SettingKey, never trusted as stored.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property int|null $updated_by_admin_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PlatformSetting extends Model
{
    use Auditable;

    protected $fillable = ['key', 'value', 'updated_by_admin_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
