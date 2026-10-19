<?php

namespace App\Repositories\Eloquent\Admin;

use App\Models\AdminExport;
use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

/**
 * @extends BaseRepository<AdminExport>
 */
final class AdminExportRepository extends BaseRepository implements AdminExportRepositoryInterface
{
    public function __construct(AdminExport $model)
    {
        parent::__construct($model);
    }

    public function findByUuidForUpdate(string $uuid): ?AdminExport
    {
        return $this->query()->where('uuid', $uuid)->lockForUpdate()->first();
    }
}
