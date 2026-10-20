<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Enums\Content\ContentStatus;
use App\Models\ServiceCategory;
use App\Models\ServiceFormSchema;
use App\Repositories\Contracts\Catalog\ServiceFormSchemaRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

/**
 * @extends BaseRepository<ServiceFormSchema>
 */
final class ServiceFormSchemaRepository extends BaseRepository implements ServiceFormSchemaRepositoryInterface
{
    public function __construct(ServiceFormSchema $model)
    {
        parent::__construct($model);
    }

    public function draftFor(ServiceCategory $category): ?ServiceFormSchema
    {
        return $this->query()->where('service_category_id', $category->getKey())->where('status', ContentStatus::Draft)->first();
    }

    public function lockDraftFor(ServiceCategory $category): ?ServiceFormSchema
    {
        return $this->query()->where('service_category_id', $category->getKey())->where('status', ContentStatus::Draft)->lockForUpdate()->first();
    }

    public function createDraft(ServiceCategory $category, array $fields): ServiceFormSchema
    {
        /** @var ServiceFormSchema $schema */
        $schema = $this->create([
            'service_category_id' => $category->getKey(),
            'version' => null,
            'fields' => $fields,
            'status' => ContentStatus::Draft,
        ]);

        return $schema;
    }

    public function createPublished(ServiceCategory $category, array $fields): ServiceFormSchema
    {
        $next = ((int) $this->query()->where('service_category_id', $category->getKey())->max('version')) + 1;

        /** @var ServiceFormSchema $schema */
        $schema = $this->create([
            'service_category_id' => $category->getKey(),
            'version' => $next,
            'fields' => $fields,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        return $schema;
    }

    public function archivePublished(ServiceCategory $category, int $exceptId): int
    {
        return $this->query()
            ->where('service_category_id', $category->getKey())
            ->where('status', ContentStatus::Published)
            ->whereKeyNot($exceptId)
            ->update(['status' => ContentStatus::Archived->value]);
    }
}
