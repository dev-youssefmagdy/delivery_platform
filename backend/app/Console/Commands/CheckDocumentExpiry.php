<?php

namespace App\Console\Commands;

use App\Services\Provider\DocumentExpiryService;
use Illuminate\Console\Command;

/**
 * `documents:check-expiry` (daily): marks expired provider and vehicle documents, sends expiry reminders (log-channel
 * stub until P3.6) and takes providers who are no longer eligible offline. Idempotent.
 */
class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';

    protected $description = 'Mark expired provider/vehicle documents, send expiry reminders and take ineligible providers offline';

    public function handle(DocumentExpiryService $expiry): int
    {
        $result = $expiry->run();

        $this->info("Expired: {$result['expired']}, reminded: {$result['reminded']}.");

        return self::SUCCESS;
    }
}
