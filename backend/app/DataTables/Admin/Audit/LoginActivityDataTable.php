<?php

namespace App\DataTables\Admin\Audit;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Admin\LoginActivityFilterData;
use App\Models\AdminLoginEvent;
use App\Repositories\Contracts\Admin\AdminLoginEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Read-only login activity (filters: email, outcome, IP, date range).
 */
final class LoginActivityDataTable extends AdminDataTable
{
    public const TABLE_ID = 'login-activity-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly AdminLoginEventRepositoryInterface $events)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<AdminLoginEvent>
     */
    public function query(): Builder
    {
        return $this->events->datatableQuery(LoginActivityFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('created_at', __('admin.login_activity.fields.time'), orderable: true),
            $this->column('email', __('admin.login_activity.fields.email'), orderable: true),
            $this->column('outcome', __('admin.login_activity.fields.outcome'), orderable: true),
            $this->column('ip', __('admin.login_activity.fields.ip'), orderable: true),
            $this->column('user_agent', __('admin.login_activity.fields.user_agent')),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->dateColumn($table, 'created_at');
        $this->enumBadgeColumn($table, 'outcome');

        $table->editColumn('email', static fn (Model $row): string => (string) ($row->getAttribute('email') ?? '—'));
        $table->editColumn('ip', static fn (Model $row): string => (string) ($row->getAttribute('ip') ?? '—'));
        $table->editColumn('user_agent', static fn (Model $row): string => Str::limit((string) ($row->getAttribute('user_agent') ?? '—'), 70));
    }
}
