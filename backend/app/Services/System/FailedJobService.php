<?php

namespace App\Services\System;

use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\FailedJob;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Console\Kernel as Artisan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Manual retry of a dead-lettered queue job (SRS 23.2). A reason is mandatory and the retry is audited with the job's uuid,
 * queue and the exception class (never the payload or the exception text, which can hold personal data).
 */
final class FailedJobService
{
    public function __construct(
        private readonly FailedJobRepositoryInterface $failedJobs,
        private readonly AuditLogger $audit,
        private readonly Artisan $artisan,
    ) {}

    /**
     * @throws BusinessRuleViolation `failed_job_reason_required`
     */
    public function retry(string $uuid, Admin $actor, ?string $reason): void
    {
        if ($reason === null || trim($reason) === '') {
            throw BusinessRuleViolation::make('failed_job_reason_required', 'reason');
        }

        $job = $this->failedJobs->findByUuid($uuid) ?? throw (new ModelNotFoundException)->setModel(FailedJob::class);

        // queue:retry pushes the payload back onto its queue and removes the failed row; it is safe to call once per uuid.
        $this->artisan->call('queue:retry', ['id' => [$job->uuid]]);

        $this->audit->record(AuditAction::Reprocessed, $job, $actor, reason: $reason, meta: [
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'exception' => self::exceptionClass($job->exception),
        ]);
    }

    public static function exceptionClass(string $exception): string
    {
        return strtok($exception, ':') ?: 'unknown';
    }
}
