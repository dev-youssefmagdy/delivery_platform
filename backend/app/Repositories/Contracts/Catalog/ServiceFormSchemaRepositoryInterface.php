<?php

namespace App\Repositories\Contracts\Catalog;

use App\Models\ServiceCategory;
use App\Models\ServiceFormSchema;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<ServiceFormSchema>
 */
interface ServiceFormSchemaRepositoryInterface extends BaseRepositoryInterface
{
    public function draftFor(ServiceCategory $category): ?ServiceFormSchema;

    public function lockDraftFor(ServiceCategory $category): ?ServiceFormSchema;

    /**
     * @param list<array<string, mixed>> $fields
     */
    public function createDraft(ServiceCategory $category, array $fields): ServiceFormSchema;

    /**
     * Copies fields into a new immutable published row with the next version number of the category.
     *
     * @param list<array<string, mixed>> $fields
     */
    public function createPublished(ServiceCategory $category, array $fields): ServiceFormSchema;

    public function archivePublished(ServiceCategory $category, int $exceptId): int;
}
