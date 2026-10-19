<?php

namespace App\Repositories\Eloquent\Admin;

use App\Models\AdminSession;
use App\Repositories\Contracts\Admin\AdminSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AdminSessionRepository implements AdminSessionRepositoryInterface
{
    public function __construct(private readonly AdminSession $model) {}

    public function create(array $attributes): AdminSession
    {
        return $this->model->newQuery()->updateOrCreate(['session_id' => $attributes['session_id']], $attributes);
    }

    public function findBySessionId(string $sessionId): ?AdminSession
    {
        return $this->model->newQuery()->where('session_id', $sessionId)->first();
    }

    public function find(int $id): ?AdminSession
    {
        return $this->model->newQuery()->find($id);
    }

    public function forAdmin(int $adminId): Collection
    {
        return $this->model->newQuery()->where('admin_id', $adminId)->orderByDesc('last_active_at')->get();
    }

    public function touch(AdminSession $session): void
    {
        $session->forceFill(['last_active_at' => now()])->save();
    }

    public function delete(AdminSession $session): void
    {
        $session->delete();
    }

    public function deleteForAdmin(int $adminId, ?string $exceptSessionId = null): array
    {
        $query = $this->model->newQuery()
            ->where('admin_id', $adminId)
            ->when($exceptSessionId !== null, static fn (Builder $query) => $query->where('session_id', '!=', $exceptSessionId));

        /** @var list<string> $ids */
        $ids = (clone $query)->pluck('session_id')->all();
        $query->delete();

        return $ids;
    }
}
