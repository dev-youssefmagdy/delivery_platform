<?php

namespace App\Console\Commands;

use App\Services\Provider\DocumentExpiryService;
use Illuminate\Console\Command;

/**
 * `documents:check-expiry` (daily): marks expired provider and vehicle documents, sends expiry reminders (push, inbox,
 * SMS and email through the notification platform) and takes providers who are no longer eligible offline. Idempotent.
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
