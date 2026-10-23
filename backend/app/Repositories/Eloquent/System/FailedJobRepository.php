<?php

namespace App\Repositories\Eloquent\System;

use App\DTOs\System\FailedJobFilterData;
use App\Models\FailedJob;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class FailedJobRepository implements FailedJobRepositoryInterface
{
    public function count(): int
    {
        return FailedJob::query()->count();
    }

    public function findByUuid(string $uuid): ?FailedJob
    {
        return FailedJob::query()->where('uuid', $uuid)->first();
    }

    public function datatableQuery(FailedJobFilterData $filters): Builder
    {
        // `exception` is selected only to derive the exception class name in the listing; it is never sent to the browser.
        return FailedJob::query()
            ->select(['id', 'uuid', 'connection', 'queue', 'failed_at', 'payload', 'exception'])
            ->when($filters->queue !== null, static fn (Builder $q) => $q->where('queue', $filters->queue))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('failed_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('failed_at', '<=', $filters->to?->endOfDay()));
    }
}
