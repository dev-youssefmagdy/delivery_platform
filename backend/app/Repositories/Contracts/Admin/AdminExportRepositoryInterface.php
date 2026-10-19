<?php

namespace App\Repositories\Contracts\Admin;

use App\Models\AdminExport;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<AdminExport>
 */
interface AdminExportRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUuidForUpdate(string $uuid): ?AdminExport;
}
