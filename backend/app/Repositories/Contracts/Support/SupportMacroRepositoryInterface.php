<?php

namespace App\Repositories\Contracts\Support;

use App\DTOs\Support\MacroFilterData;
use App\Enums\Support\TicketCategory;
use App\Models\SupportMacro;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepositoryInterface<SupportMacro>
 */
interface SupportMacroRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<SupportMacro>
     */
    public function datatableQuery(MacroFilterData $filters): Builder;

    /**
     * Active macros an agent can pick for a ticket of this category (macros without a category apply to every ticket).
     *
     * @return Collection<int, SupportMacro>
     */
    public function activeFor(TicketCategory $category): Collection;

    public function findActiveByUuid(string $uuid): ?SupportMacro;
}
