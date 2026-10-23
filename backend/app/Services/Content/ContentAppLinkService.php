<?php

namespace App\Services\Content;

use App\DTOs\Content\AppLinkData;
use App\Enums\Common\AuditAction;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ContentAppLink;
use App\Repositories\Contracts\Content\ContentAppLinkRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Store links of the mobile apps. Links are live as soon as they are active, so every change is audited and
 * validated as an https URL.
 */
final class ContentAppLinkService
{
    public function __construct(
        private readonly ContentAppLinkRepositoryInterface $links,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly ContentCache $cache,
    ) {}

    public function create(AppLinkData $data, Admin $actor): ContentAppLink
    {
        $link = $this->db->transaction(function () use ($data, $actor): ContentAppLink {
            /** @var ContentAppLink $link */
            $link = $this->links->create($data->attributes());
            $this->audit->record(AuditAction::Created, $link, $actor, after: $link->auditSnapshot());

            return $link;
        });
        $this->cache->bump();

        return $link;
    }

    public function update(ContentAppLink $link, AppLinkData $data, Admin $actor): ContentAppLink
    {
        $updated = $this->db->transaction(function () use ($link, $data, $actor): ContentAppLink {
            $locked = $this->links->lockById($link->getKey());

            if (! $locked instanceof ContentAppLink) {
                throw (new ModelNotFoundException)->setModel(ContentAppLink::class);
            }

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->links->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
        $this->cache->bump();

        return $updated;
    }

    public function delete(ContentAppLink $link, Admin $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($link, $actor, $reason): void {
            $before = $link->auditSnapshot();
            $this->links->delete($link);
            $this->audit->record(AuditAction::Deleted, $link, $actor, before: $before, reason: $reason);
        });
        $this->cache->bump();
    }
}
