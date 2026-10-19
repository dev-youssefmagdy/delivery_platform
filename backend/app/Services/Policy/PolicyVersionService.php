<?php

namespace App\Services\Policy;

use App\DTOs\Policy\PolicyVersionData;
use App\Enums\Common\AuditAction;
use App\Enums\Content\ContentStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Config\AppConfigCache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Policy version lifecycle (SRS §13.3): drafts are editable and deletable; publishing assigns the next version
 * number of the type under a per-type lock, archives the previous published version and freezes the row.
 * Published and archived versions are never changed (consents reference them).
 */
final class PolicyVersionService
{
    public function __construct(
        private readonly PolicyVersionRepositoryInterface $policies,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly AppConfigCache $appConfig,
    ) {}

    public function create(PolicyVersionData $data, Admin $actor): PolicyVersion
    {
        return $this->db->transaction(function () use ($data, $actor): PolicyVersion {
            /** @var PolicyVersion $policy */
            $policy = $this->policies->create($data->attributes() + ['status' => ContentStatus::Draft]);
            $this->audit->record(AuditAction::Created, $policy, $actor, after: $policy->auditSnapshot());

            return $policy;
        });
    }

    public function update(PolicyVersion $policy, PolicyVersionData $data, Admin $actor): PolicyVersion
    {
        return $this->db->transaction(function () use ($policy, $data, $actor): PolicyVersion {
            $locked = $this->lock($policy);
            $this->assertDraft($locked);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->policies->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function delete(PolicyVersion $policy, Admin $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($policy, $actor, $reason): void {
            $locked = $this->lock($policy);
            $this->assertDraft($locked, 'policy_not_deletable');

            $before = $locked->auditSnapshot();
            $this->policies->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    public function publish(PolicyVersion $policy, string $reason, Admin $actor): PolicyVersion
    {
        $published = $this->db->transaction(function () use ($policy, $reason, $actor): PolicyVersion {
            $versions = $this->policies->lockByType($policy->type);
            $target = $versions->firstWhere('id', $policy->getKey());

            if (! $target instanceof PolicyVersion) {
                throw (new ModelNotFoundException)->setModel(PolicyVersion::class);
            }

            $this->assertDraft($target, 'policy_not_publishable');

            if (trim((string) $target->getTranslation('title', 'ar', false)) === '' || trim((string) $target->getTranslation('content', 'ar', false)) === '') {
                throw BusinessRuleViolation::make('policy_arabic_required');
            }

            $next = ((int) $versions->max('version')) + 1;

            foreach ($versions->where('status', ContentStatus::Published) as $previous) {
                /** @var PolicyVersion $previous */
                $this->policies->update($previous, ['status' => ContentStatus::Archived, 'lock_version' => $previous->lock_version + 1]);
                $this->audit->record(
                    AuditAction::StatusChanged,
                    $previous,
                    $actor,
                    ['status' => ContentStatus::Published->value],
                    ['status' => ContentStatus::Archived->value],
                    $reason,
                    ['superseded_by_version' => $next],
                );
            }

            $before = ['status' => ContentStatus::Draft->value, 'version' => null];
            $this->policies->update($target, [
                'status' => ContentStatus::Published,
                'version' => $next,
                'published_at' => now(),
                'published_by_admin_id' => $actor->getKey(),
                'lock_version' => $target->lock_version + 1,
            ]);
            $this->audit->record(AuditAction::Published, $target, $actor, $before, ['status' => ContentStatus::Published->value, 'version' => $next], $reason);

            return $target;
        });

        $this->appConfig->forget();

        return $published;
    }

    public function details(PolicyVersion $policy): PolicyVersion
    {
        return $this->policies->loadPublisher($policy);
    }

    private function lock(PolicyVersion $policy): PolicyVersion
    {
        $locked = $this->policies->lockById($policy->getKey());

        return $locked instanceof PolicyVersion ? $locked : throw (new ModelNotFoundException)->setModel(PolicyVersion::class);
    }

    private function assertDraft(PolicyVersion $policy, string $code = 'policy_not_editable'): void
    {
        if (! $policy->isDraft()) {
            throw BusinessRuleViolation::make($code);
        }
    }
}
