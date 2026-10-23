<?php

namespace App\Services\Content;

use App\DTOs\Content\CategoryData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ContentCategory;
use App\Repositories\Contracts\Content\ContentCategoryRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * FAQ and article categories. Writes are audited; a category in use cannot be deleted (deactivate it instead).
 */
final class ContentCategoryService
{
    public function __construct(
        private readonly ContentCategoryRepositoryInterface $categories,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly ContentCache $cache,
    ) {}

    public function create(CategoryData $data, Admin $actor): ContentCategory
    {
        $category = $this->db->transaction(function () use ($data, $actor): ContentCategory {
            /** @var ContentCategory $category */
            $category = $this->categories->create($data->attributes());
            $this->audit->record(AuditAction::Created, $category, $actor, after: $category->auditSnapshot());

            return $category;
        });
        $this->cache->bump();

        return $category;
    }

    public function update(ContentCategory $category, CategoryData $data, Admin $actor): ContentCategory
    {
        $updated = $this->db->transaction(function () use ($category, $data, $actor): ContentCategory {
            $locked = $this->categories->lockById($category->getKey());

            if (! $locked instanceof ContentCategory) {
                throw (new ModelNotFoundException)->setModel(ContentCategory::class);
            }

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->categories->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
        $this->cache->bump();

        return $updated;
    }

    public function delete(ContentCategory $category, Admin $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($category, $actor, $reason): void {
            $locked = $this->categories->lockById($category->getKey());

            if (! $locked instanceof ContentCategory) {
                return;
            }

            if ($this->categories->usageCount($locked) > 0) {
                throw BusinessRuleViolation::make('category_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->categories->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
        $this->cache->bump();
    }
}
