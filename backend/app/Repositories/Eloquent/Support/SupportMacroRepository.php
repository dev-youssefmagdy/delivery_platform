<?php

namespace App\Repositories\Eloquent\Support;

use App\DTOs\Support\MacroFilterData;
use App\Enums\Support\TicketCategory;
use App\Models\SupportMacro;
use App\Repositories\Contracts\Support\SupportMacroRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<SupportMacro>
 */
final class SupportMacroRepository extends BaseRepository implements SupportMacroRepositoryInterface
{
    public function __construct(SupportMacro $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(MacroFilterData $filters): Builder
    {
        return $this->query()
            ->when($filters->title !== null, static fn (Builder $query) => $query->where(static fn (Builder $inner) => $inner->where('title_ar', 'like', '%'.$filters->title.'%')->orWhere('title_en', 'like', '%'.$filters->title.'%')))
            ->when($filters->categories !== [], static fn (Builder $query) => $query->whereIn('category', array_map(static fn (TicketCategory $case): string => $case->value, $filters->categories)))
            ->when($filters->active !== null, static fn (Builder $query) => $query->where('active', $filters->active));
    }

    public function activeFor(TicketCategory $category): Collection
    {
        return $this->query()
            ->where('active', true)
            ->where(static fn (Builder $query) => $query->whereNull('category')->orWhere('category', $category->value))
            ->orderBy('title_ar')
            ->get()
            ->toBase();
    }

    public function findActiveByUuid(string $uuid): ?SupportMacro
    {
        return $this->query()->where('uuid', $uuid)->where('active', true)->first();
    }
}
