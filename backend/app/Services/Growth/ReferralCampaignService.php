<?php

namespace App\Services\Growth;

use App\DTOs\Growth\ReferralCampaignData;
use App\Enums\Common\AuditAction;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ReferralCampaign;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin lifecycle of referral campaigns: create, edit (draft or paused only, `lock_version`), activate, pause and archive (mandatory
 * reason), and budget adjustment (mandatory reason, never below what was spent). Every write is a transaction with an audit row.
 */
final class ReferralCampaignService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(Admin $actor, ReferralCampaignData $data): ReferralCampaign
    {
        return $this->db->transaction(function () use ($actor, $data): ReferralCampaign {
            $campaign = $this->referrals->createCampaign($data->attributes() + [
                'currency' => (string) $this->settings->string(SettingKey::DefaultCurrency),
                'status' => CampaignStatus::Draft,
                'created_by_admin_id' => $actor->id,
            ]);
            $this->audit->record(AuditAction::Created, $campaign, $actor, after: $campaign->auditSnapshot());

            return $campaign;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     * @throws BusinessRuleViolation `campaign_not_editable`, `campaign_budget_below_spent`
     */
    public function update(ReferralCampaign $campaign, Admin $actor, ReferralCampaignData $data): ReferralCampaign
    {
        return $this->db->transaction(function () use ($campaign, $actor, $data): ReferralCampaign {
            $locked = $this->referrals->lockCampaign($campaign->id) ?? throw (new ModelNotFoundException)->setModel(ReferralCampaign::class);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if (! $locked->status->isEditable()) {
                throw BusinessRuleViolation::make('campaign_not_editable');
            }

            if ($data->budgetMinor < $locked->spent_minor) {
                throw BusinessRuleViolation::make('campaign_budget_below_spent', 'budget_minor');
            }

            $before = $locked->auditSnapshot();
            // The audience identifies the campaign and cannot change after creation.
            $attributes = $data->attributes();
            unset($attributes['audience']);
            $this->referrals->updateCampaign($locked, $attributes + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `campaign_transition_invalid`, `campaign_reason_required`, `campaign_incomplete`
     */
    public function transition(ReferralCampaign $campaign, Admin $actor, CampaignStatus $target, ?string $reason): ReferralCampaign
    {
        return $this->db->transaction(function () use ($campaign, $actor, $target, $reason): ReferralCampaign {
            $locked = $this->referrals->lockCampaign($campaign->id) ?? throw (new ModelNotFoundException)->setModel(ReferralCampaign::class);

            if (! $locked->status->canMoveTo($target)) {
                throw BusinessRuleViolation::make('campaign_transition_invalid');
            }

            if ($target->needsReason() && ($reason === null || trim($reason) === '')) {
                throw BusinessRuleViolation::make('campaign_reason_required', 'reason');
            }

            if ($target === CampaignStatus::Active) {
                $total = $locked->referrer_reward_minor + $locked->referee_reward_minor;

                if ($total <= 0 || $locked->budget_minor < $total || ($locked->ends_at !== null && $locked->ends_at->isPast())) {
                    throw BusinessRuleViolation::make('campaign_incomplete');
                }
            }

            $before = ['status' => $locked->status->value];
            $this->referrals->updateCampaign($locked, ['status' => $target, 'status_reason' => $reason, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: $before, after: ['status' => $target->value], reason: $reason, meta: ['name' => $locked->name]);

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `campaign_reason_required`, `campaign_budget_below_spent`
     */
    public function adjustBudget(ReferralCampaign $campaign, Admin $actor, int $budgetMinor, string $reason): ReferralCampaign
    {
        return $this->db->transaction(function () use ($campaign, $actor, $budgetMinor, $reason): ReferralCampaign {
            $locked = $this->referrals->lockCampaign($campaign->id) ?? throw (new ModelNotFoundException)->setModel(ReferralCampaign::class);

            if (trim($reason) === '') {
                throw BusinessRuleViolation::make('campaign_reason_required', 'reason');
            }

            if ($budgetMinor < $locked->spent_minor || $locked->status === CampaignStatus::Archived) {
                throw BusinessRuleViolation::make('campaign_budget_below_spent', 'budget_minor');
            }

            $before = ['budget_minor' => $locked->budget_minor];
            $this->referrals->updateCampaign($locked, ['budget_minor' => $budgetMinor, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, before: $before, after: ['budget_minor' => $budgetMinor], reason: $reason, meta: ['name' => $locked->name]);

            return $locked;
        });
    }
}
