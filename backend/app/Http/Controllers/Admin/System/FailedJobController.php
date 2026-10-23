<?php

namespace App\Http\Controllers\Admin\System;

use App\DataTables\Admin\System\FailedJobsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\System\RetryFailedJobRequest;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use App\Services\System\FailedJobService;
use App\Support\Http\AdminFormResponse;

/**
 * Failed queue jobs (system.queues): list and audited manual retry.
 */
final class FailedJobController extends AdminController
{
    public function index(FailedJobsDataTable $table, FailedJobRepositoryInterface $failedJobs): mixed
    {
        return $table->render('admin.system.failed-jobs.index', ['total' => $failedJobs->count()]);
    }

    public function retry(RetryFailedJobRequest $request, string $uuid, FailedJobService $service): AdminFormResponse
    {
        $service->retry($uuid, $this->actor(), $request->reason());

        return $this->formResponse(__('system.queues.retried'))->reloadTable(FailedJobsDataTable::TABLE_ID);
    }
}
