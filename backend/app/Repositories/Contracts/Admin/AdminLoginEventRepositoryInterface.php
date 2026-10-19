<?php

namespace App\Repositories\Contracts\Admin;

use App\DTOs\Admin\LoginActivityFilterData;
use App\Models\AdminLoginEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Append-only login activity.
 */
interface AdminLoginEventRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function append(array $attributes): AdminLoginEvent;

    /**
     * @return Builder<AdminLoginEvent>
     */
    public function datatableQuery(LoginActivityFilterData $filters): Builder;
}
