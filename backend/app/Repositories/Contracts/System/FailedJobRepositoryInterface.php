<?php

namespace App\Repositories\Contracts\System;

use App\DTOs\System\FailedJobFilterData;
use App\Models\FailedJob;
use Illuminate\Database\Eloquent\Builder;

interface FailedJobRepositoryInterface
{
    public function count(): int;

    public function findByUuid(string $uuid): ?FailedJob;

    /**
     * @return Builder<FailedJob>
     */
    public function datatableQuery(FailedJobFilterData $filters): Builder;
}
