<?php

namespace App\Repositories\Eloquent\Admin;

use App\DTOs\Admin\LoginActivityFilterData;
use App\Enums\Admin\AdminLoginOutcome;
use App\Models\AdminLoginEvent;
use App\Repositories\Contracts\Admin\AdminLoginEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class AdminLoginEventRepository implements AdminLoginEventRepositoryInterface
{
    public function __construct(private readonly AdminLoginEvent $model) {}

    public function append(array $attributes): AdminLoginEvent
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function datatableQuery(LoginActivityFilterData $filters): Builder
    {
        return $this->model->newQuery()
            ->with('admin:id,name')
            ->when($filters->email, static function (Builder $query, string $email): void {
                $query->where('email', 'like', '%'.addcslashes($email, '%_\\').'%');
            })
            ->when($filters->outcomes !== [], static fn (Builder $query) => $query->whereIn(
                'outcome',
                array_map(static fn (AdminLoginOutcome $outcome): string => $outcome->value, $filters->outcomes),
            ))
            ->when($filters->ip, static fn (Builder $query, string $ip) => $query->where('ip', $ip))
            ->when($filters->from, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->to, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }
}
