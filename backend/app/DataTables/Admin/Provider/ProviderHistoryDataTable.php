<?php

namespace App\DataTables\Admin\Provider;

use App\Enums\Common\ActorType;
use App\Models\ProviderStatusEvent;
use App\Repositories\Contracts\Provider\ProviderStatusEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Verification history: every status change of the application with who made it and why.
 */
final class ProviderHistoryDataTable extends ProviderTabDataTable
{
    public const TABLE_ID = 'provider-history-table';

    protected array $defaultOrder = [[0, 'desc']];

    public function __construct(private readonly ProviderStatusEventRepositoryInterface $events)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ProviderStatusEvent>
     */
    public function query(): Builder
    {
        return $this->events->datatableQuery($this->provider());
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.providers.history.time'), orderable: true),
            $this->column('from_status', __('admin.providers.history.from')),
            $this->column('to_status', __('admin.providers.history.to')),
            $this->column('actor', __('admin.providers.history.actor')),
            $this->column('reason', __('admin.providers.history.reason')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'created_at', 'from_status', 'to_status', 'actor', 'reason']);
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'from_status');
        $this->enumBadgeColumn($table, 'to_status');
        $table->addColumn('actor', static function (Model $row): string {
            if (! $row instanceof ProviderStatusEvent) {
                return '';
            }

            if ($row->actor_type === ActorType::Admin->value && $row->actor_id !== null) {
                return $row->admin->name ?? ActorType::Admin->label();
            }

            return ActorType::tryFrom($row->actor_type)?->label() ?? $row->actor_type;
        });
        $table->editColumn('reason', static fn (Model $row): string => $row instanceof ProviderStatusEvent && $row->reason !== null ? Str::limit($row->reason, 160) : '—');
    }
}
