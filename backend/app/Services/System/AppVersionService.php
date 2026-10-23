<?php

namespace App\Services\System;

use App\DTOs\System\AppVersionData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\AppVersion;
use App\Repositories\Contracts\System\AppVersionRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Config\AppConfigCache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Minimum version, force update and store link per app and platform (SRS 16.15). Rows feed GET /config/app, so every write
 * is audited and busts the config cache. A forced update without a store link is refused: the app would show a dead end.
 */
final class AppVersionService
{
    public function __construct(
        private readonly AppVersionRepositoryInterface $versions,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly AppConfigCache $configCache,
    ) {}

    /**
     * @throws BusinessRuleViolation `app_version_duplicate`, `app_version_store_url_required`
     */
    public function create(Admin $actor, AppVersionData $data): AppVersion
    {
        return $this->db->transaction(function () use ($actor, $data): AppVersion {
            $this->assertRules($data, null);

            $version = $this->versions->create($this->attributes($data));
            $this->audit->record(AuditAction::Created, $version, $actor, after: $version->auditSnapshot());
            $this->configCache->forget();

            return $version;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     * @throws BusinessRuleViolation `app_version_duplicate`, `app_version_store_url_required`
     */
    public function update(AppVersion $version, Admin $actor, AppVersionData $data): AppVersion
    {
        return $this->db->transaction(function () use ($version, $actor, $data): AppVersion {
            $locked = $this->versions->lockById($version->id) ?? throw (new ModelNotFoundException)->setModel(AppVersion::class);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $this->assertRules($data, $locked->id);
            $before = $locked->auditSnapshot();
            $this->versions->update($locked, $this->attributes($data) + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: $locked->auditSnapshot());
            $this->configCache->forget();

            return $locked;
        });
    }

    private function assertRules(AppVersionData $data, ?int $exceptId): void
    {
        if ($this->versions->exists($data->app->value, $data->platform->value, $exceptId)) {
            throw BusinessRuleViolation::make('app_version_duplicate', 'platform');
        }

        if ($data->forceUpdate && $data->storeUrl === null) {
            throw BusinessRuleViolation::make('app_version_store_url_required', 'store_url');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(AppVersionData $data): array
    {
        return [
            'app' => $data->app,
            'platform' => $data->platform,
            'min_version' => $data->minVersion,
            'latest_version' => $data->latestVersion,
            'force_update' => $data->forceUpdate,
            'store_url' => $data->storeUrl,
        ];
    }
}
