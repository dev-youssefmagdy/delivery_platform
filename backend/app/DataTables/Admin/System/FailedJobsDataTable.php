<?php

namespace App\DataTables\Admin\System;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\System\FailedJobFilterData;
use App\Models\FailedJob;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use App\Services\System\FailedJobService;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Failed queue jobs (filters: queue, failed range). Shows the job class and exception class only: payloads and exception
 * messages can contain personal data and are never rendered. Retry needs a reason and is audited.
 */
final class FailedJobsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'failed-jobs-table';

    protected array $defaultOrder = [[4, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly FailedJobRepositoryInterface $failedJobs)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<FailedJob>
     */
    public function query(): Builder
    {
        return $this->failedJobs->datatableQuery(FailedJobFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('job', __('system.queues.fields.job')),
            $this->column('queue', __('system.queues.fields.queue'), orderable: true, name: 'queue'),
            $this->column('connection', __('system.queues.fields.connection')),
            $this->column('exception_class', __('system.queues.fields.exception')),
            $this->column('failed_at', __('system.queues.fields.failed_at'), orderable: true, name: 'failed_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['job', 'queue', 'connection', 'exception_class', 'failed_at', 'actions']);

        $table->addColumn('job', static function (FailedJob $row): string {
            $payload = json_decode((string) $row->getAttributes()['payload'], true);

            return '<span dir="ltr">'.e(is_array($payload) && isset($payload['displayName']) ? (string) $payload['displayName'] : '—').'</span>';
        });
        $this->rawColumn('job');
        $table->addColumn('exception_class', static fn (FailedJob $row): string => '<span dir="ltr">'.e(FailedJobService::exceptionClass((string) $row->getAttributes()['exception'])).'</span>');
        $this->rawColumn('exception_class');
        $this->dateColumn($table, 'failed_at');

        $this->actionsColumn($table, static fn (FailedJob $row): array => [
            TableAction::confirm(
                __('system.queues.retry'),
                route('admin.failed-jobs.retry', $row->uuid),
                'retry',
                $row,
                icon: 'refresh',
                variant: 'secondary',
                reasonRequired: true,
                confirm: ['title' => __('system.queues.confirm_title'), 'message' => __('system.queues.confirm_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
