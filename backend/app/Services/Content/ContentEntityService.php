<?php

namespace App\Services\Content;

use App\DTOs\Content\ContentData;
use App\Enums\Common\AuditAction;
use App\Enums\Content\ContentType;
use App\Enums\Content\ContentWorkflowStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Create / edit / delete of the workflow entities (pages, articles, FAQs, banners, menus). Only drafts can be edited.
 * Every write is one transaction: row lock, optimistic lock_version check, sanitized HTML derived from the sources,
 * audit entry with before/after, and a revision snapshot.
 */
final class ContentEntityService
{
    /** Identity fields are not touched when an older revision is restored. */
    private const IDENTITY = ['slug', 'kind', 'type', 'location'];

    public function __construct(
        private readonly ContentRepositoryRegistry $repositories,
        private readonly ContentPayloadNormalizer $normalizer,
        private readonly ContentRevisionService $revisions,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly \App\Repositories\Contracts\Content\ContentRevisionRepositoryInterface $revisionStore,
    ) {}

    public function create(ContentType $type, ContentData $data, Admin $actor): Model
    {
        return $this->db->transaction(function () use ($type, $data, $actor): Model {
            $content = $this->repositories->for($type)->create(
                $this->normalizer->derive($type, $data->attributes()) + [
                    'status' => ContentWorkflowStatus::Draft,
                    'created_by_admin_id' => $actor->getKey(),
                    'updated_by_admin_id' => $actor->getKey(),
                ],
            );

            $this->audit->record(AuditAction::Created, $content, $actor, after: $this->auditSnapshot($content));
            $this->revisions->capture($content, $actor, 'created');

            return $content;
        });
    }

    public function update(Model $content, ContentData $data, Admin $actor): Model
    {
        $type = ContentType::forModel($content);

        return $this->db->transaction(function () use ($type, $content, $data, $actor): Model {
            $locked = $this->lock($type, $content);
            $this->assertEditable($locked);

            if ($data->lockVersion() !== null && $data->lockVersion() !== (int) $locked->getAttribute('lock_version')) {
                throw ConflictException::make('stale_version');
            }

            $before = $this->auditSnapshot($locked);
            $this->repositories->for($type)->update($locked, $this->normalizer->derive($type, $data->attributes()) + [
                'updated_by_admin_id' => $actor->getKey(),
                'lock_version' => (int) $locked->getAttribute('lock_version') + 1,
            ]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $this->auditSnapshot($locked));
            $this->revisions->capture($locked, $actor, 'updated');

            return $locked;
        });
    }

    public function delete(Model $content, Admin $actor, ?string $reason = null): void
    {
        $type = ContentType::forModel($content);

        $this->db->transaction(function () use ($type, $content, $actor, $reason): void {
            $locked = $this->lock($type, $content);

            if ($locked->getAttribute('status') !== ContentWorkflowStatus::Draft || $locked->getAttribute('published_at') !== null) {
                throw BusinessRuleViolation::make('content_not_deletable');
            }

            $before = $this->auditSnapshot($locked);
            $this->repositories->for($type)->delete($locked);
            $this->revisionStore->deleteForEntity($type, (int) $locked->getKey());
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    /**
     * Copies the fields of an earlier revision back into the draft (the result is a new revision; nothing is lost).
     */
    public function restoreRevision(Model $content, int $number, Admin $actor, ?string $reason = null): Model
    {
        $type = ContentType::forModel($content);

        return $this->db->transaction(function () use ($type, $content, $number, $actor, $reason): Model {
            $locked = $this->lock($type, $content);
            $this->assertEditable($locked);

            $revision = $this->revisions->find($locked, $number) ?? throw (new ModelNotFoundException)->setModel($type->modelClass());
            $attributes = array_diff_key($revision->snapshot, array_flip(self::IDENTITY));

            $before = $this->auditSnapshot($locked);
            $this->repositories->for($type)->update($locked, $this->normalizer->derive($type, $attributes) + [
                'updated_by_admin_id' => $actor->getKey(),
                'lock_version' => (int) $locked->getAttribute('lock_version') + 1,
            ]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $this->auditSnapshot($locked), $reason, ['restored_revision' => $number]);
            $this->revisions->capture($locked, $actor, 'restored', $reason);

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(Model $content): array
    {
        /** @var \App\Models\ContentPage $content */
        return $content->auditSnapshot();
    }

    private function lock(ContentType $type, Model $content): Model
    {
        return $this->repositories->for($type)->lockById($content->getKey()) ?? throw (new ModelNotFoundException)->setModel($type->modelClass());
    }

    private function assertEditable(Model $content): void
    {
        $status = $content->getAttribute('status');

        if (! $status instanceof ContentWorkflowStatus || ! $status->isEditable()) {
            throw BusinessRuleViolation::make('content_not_editable');
        }
    }
}
