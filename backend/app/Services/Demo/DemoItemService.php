<?php

namespace App\Services\Demo;

use App\DTOs\Demo\DemoItemData;
use App\DTOs\Demo\DemoItemDetails;
use App\Enums\Common\AuditAction;
use App\Enums\Demo\DemoItemStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\DemoItem;
use App\Repositories\Contracts\Demo\DemoItemRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Storage;

/**
 * Demo Item business rules: transactional, audited writes; optimistic locking (409 stale_version); the
 * unique code is re-checked by the database under concurrency (422 code_taken); archiving requires a reason.
 */
final class DemoItemService
{
    private const COVER_DISK = 'public';

    private const COVER_DIRECTORY = 'demo-items';

    public function __construct(
        private readonly DemoItemRepositoryInterface $demoItems,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(DemoItemData $data, ?Authenticatable $actor): DemoItem
    {
        $coverPath = $data->cover?->store(self::COVER_DIRECTORY, self::COVER_DISK) ?: null;

        try {
            return $this->db->transaction(function () use ($data, $actor, $coverPath): DemoItem {
                $demoItem = $this->demoItems->create($data->attributes() + ['cover_path' => $coverPath]);

                $this->audit->record(AuditAction::Created, $demoItem, $actor, after: $demoItem->auditSnapshot());

                return $demoItem;
            });
        } catch (UniqueConstraintViolationException) {
            $this->deleteCover($coverPath);

            throw BusinessRuleViolation::make('code_taken', 'code');
        }
    }

    public function update(DemoItem $demoItem, DemoItemData $data, ?Authenticatable $actor): DemoItem
    {
        $newCover = $data->cover?->store(self::COVER_DIRECTORY, self::COVER_DISK) ?: null;

        try {
            [$updated, $oldCover] = $this->db->transaction(function () use ($demoItem, $data, $actor, $newCover): array {
                $locked = $this->demoItems->lockById($demoItem->getKey()) ?? throw (new ModelNotFoundException)->setModel(DemoItem::class);

                if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                    throw ConflictException::make('stale_version');
                }

                if ($locked->status === DemoItemStatus::Archived) {
                    throw BusinessRuleViolation::make('demo_item_archived', 'status');
                }

                $before = $locked->auditSnapshot();
                $oldCover = $locked->cover_path;
                $attributes = $data->attributes() + ['lock_version' => $locked->lock_version + 1];

                if ($newCover !== null) {
                    $attributes['cover_path'] = $newCover;
                }

                $this->demoItems->update($locked, $attributes);
                $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

                return [$locked, $newCover !== null ? $oldCover : null];
            });
        } catch (UniqueConstraintViolationException) {
            $this->deleteCover($newCover);

            throw BusinessRuleViolation::make('code_taken', 'code');
        } catch (ConflictException|BusinessRuleViolation $e) {
            $this->deleteCover($newCover);

            throw $e;
        }

        $this->deleteCover($oldCover);

        return $updated;
    }

    /**
     * Dangerous action with a mandatory reason (stored on the record and in the audit log).
     */
    public function archive(DemoItem $demoItem, string $reason, ?Authenticatable $actor): DemoItem
    {
        return $this->db->transaction(function () use ($demoItem, $reason, $actor): DemoItem {
            $locked = $this->demoItems->lockById($demoItem->getKey()) ?? throw (new ModelNotFoundException)->setModel(DemoItem::class);

            if ($locked->status === DemoItemStatus::Archived) {
                throw BusinessRuleViolation::make('demo_item_archived');
            }

            $before = ['status' => $locked->status->value];

            $this->demoItems->update($locked, [
                'status' => DemoItemStatus::Archived,
                'archived_reason' => $reason,
                'archived_at' => now(),
                'lock_version' => $locked->lock_version + 1,
            ]);

            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, $before, ['status' => DemoItemStatus::Archived->value], $reason);

            return $locked;
        });
    }

    public function delete(DemoItem $demoItem, ?Authenticatable $actor, ?string $reason = null): void
    {
        $cover = $demoItem->cover_path;

        $this->db->transaction(function () use ($demoItem, $actor, $reason): void {
            $before = $demoItem->auditSnapshot();
            $this->demoItems->delete($demoItem);

            $this->audit->record(AuditAction::Deleted, $demoItem, $actor, before: $before, reason: $reason);
        });

        $this->deleteCover($cover);
    }

    /**
     * Counts for the index stat tiles.
     *
     * @return array{total: int, active: int, draft: int, archived: int}
     */
    public function stats(): array
    {
        $counts = $this->demoItems->countByStatus();

        return [
            'total' => array_sum($counts),
            'active' => $counts[DemoItemStatus::Active->value] ?? 0,
            'draft' => $counts[DemoItemStatus::Draft->value] ?? 0,
            'archived' => $counts[DemoItemStatus::Archived->value] ?? 0,
        ];
    }

    /**
     * Everything the show/edit/quick-view screens need.
     */
    public function details(DemoItem $demoItem): DemoItemDetails
    {
        $demoItem = $this->demoItems->loadDisplayRelations($demoItem);

        return new DemoItemDetails(
            $demoItem,
            $demoItem->cover_path === null ? null : Storage::disk(self::COVER_DISK)->url($demoItem->cover_path),
        );
    }

    private function deleteCover(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::COVER_DISK)->delete($path);
        }
    }
}
