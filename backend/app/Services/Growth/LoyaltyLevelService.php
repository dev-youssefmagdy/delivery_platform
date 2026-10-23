<?php

namespace App\Services\Growth;

use App\DTOs\Growth\LoyaltyLevelData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\LoyaltyLevel;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin management of loyalty levels. Thresholds are unique; deactivating a level needs a reason and keeps the awards already given
 * (nothing is clawed back); a level a customer reached is never deleted.
 */
final class LoyaltyLevelService
{
    public function __construct(
        private readonly LoyaltyRepositoryInterface $loyalty,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `loyalty_threshold_taken`
     */
    public function create(Admin $actor, LoyaltyLevelData $data): LoyaltyLevel
    {
        if ($this->loyalty->levelWithThresholdExists($data->minCompletedJobs, null)) {
            throw BusinessRuleViolation::make('loyalty_threshold_taken', 'min_completed_jobs');
        }

        return $this->db->transaction(function () use ($actor, $data): LoyaltyLevel {
            $level = $this->loyalty->createLevel($data->attributes() + ['currency' => (string) $this->settings->string(SettingKey::DefaultCurrency)]);
            $this->audit->record(AuditAction::Created, $level, $actor, after: $level->auditSnapshot());

            return $level;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     * @throws BusinessRuleViolation `loyalty_threshold_taken`
     */
    public function update(LoyaltyLevel $level, Admin $actor, LoyaltyLevelData $data): LoyaltyLevel
    {
        return $this->db->transaction(function () use ($level, $actor, $data): LoyaltyLevel {
            $locked = $this->loyalty->lockLevel($level->id) ?? throw (new ModelNotFoundException)->setModel(LoyaltyLevel::class);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($this->loyalty->levelWithThresholdExists($data->minCompletedJobs, $locked->id)) {
                throw BusinessRuleViolation::make('loyalty_threshold_taken', 'min_completed_jobs');
            }

            $before = $locked->auditSnapshot();
            $this->loyalty->updateLevel($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `loyalty_reason_required`
     */
    public function setActive(LoyaltyLevel $level, Admin $actor, bool $active, ?string $reason): LoyaltyLevel
    {
        return $this->db->transaction(function () use ($level, $actor, $active, $reason): LoyaltyLevel {
            $locked = $this->loyalty->lockLevel($level->id) ?? throw (new ModelNotFoundException)->setModel(LoyaltyLevel::class);

            if (! $active && ($reason === null || trim($reason) === '')) {
                throw BusinessRuleViolation::make('loyalty_reason_required', 'reason');
            }

            $before = ['is_active' => $locked->is_active];
            $this->loyalty->updateLevel($locked, ['is_active' => $active, 'status_reason' => $active ? null : $reason, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: $before, after: ['is_active' => $active], reason: $reason, meta: ['members' => $this->loyalty->membersAtLevel($locked->id)]);

            return $locked;
        });
    }
}
