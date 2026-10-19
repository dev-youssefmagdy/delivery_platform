<?php

namespace App\Repositories\Eloquent\Settings;

use App\Exceptions\ConflictException;
use App\Models\PlatformSetting;
use App\Repositories\Contracts\Settings\SettingsRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * @extends BaseRepository<PlatformSetting>
 */
final class SettingsRepository extends BaseRepository implements SettingsRepositoryInterface
{
    public function __construct(PlatformSetting $model)
    {
        parent::__construct($model);
    }

    public function storedValues(): array
    {
        $values = [];

        foreach ($this->query()->get(['key', 'value']) as $row) {
            $values[$row->key] = $row->value;
        }

        return $values;
    }

    public function lockByKeys(array $keys): Collection
    {
        return $this->query()->whereIn('key', $keys)->lockForUpdate()->get();
    }

    public function put(string $key, mixed $value, ?int $adminId): PlatformSetting
    {
        $row = $this->query()->where('key', $key)->first();

        if ($row === null) {
            try {
                return $this->create(['key' => $key, 'value' => $value, 'updated_by_admin_id' => $adminId]);
            } catch (UniqueConstraintViolationException) {
                throw ConflictException::make('stale_version');
            }
        }

        return $this->update($row, ['value' => $value, 'updated_by_admin_id' => $adminId]);
    }
}
