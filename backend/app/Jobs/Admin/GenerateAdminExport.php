<?php

namespace App\Jobs\Admin;

use App\Enums\Common\QueueName;
use App\Services\Admin\AdminExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Builds the CSV for a queued admin export (Plan §5.4). Idempotent: finished exports are skipped.
 */
class GenerateAdminExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly int $exportId)
    {
        $this->onQueue(QueueName::Low->value);
    }

    public function handle(AdminExportService $exports): void
    {
        $exports->generate($this->exportId);
    }
}
