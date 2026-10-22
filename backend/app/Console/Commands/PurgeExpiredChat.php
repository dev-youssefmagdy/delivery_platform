<?php

namespace App\Console\Commands;

use App\Services\Support\BookingChatService;
use Illuminate\Console\Command;

/**
 * `chat:purge-expired` (daily): the chat retention hook (SRS §14.2). Removes text and images of conversations older than
 * `support.chat.retention_days`, except while a ticket about the booking is still open. Idempotent.
 */
class PurgeExpiredChat extends Command
{
    protected $signature = 'chat:purge-expired {--batches=20 : Maximum batches of 500 messages per run}';

    protected $description = 'Remove chat messages past the retention period';

    public function handle(BookingChatService $chat): int
    {
        $total = 0;

        for ($i = 0; $i < max(1, (int) $this->option('batches')); $i++) {
            $purged = $chat->purgeExpired();
            $total += $purged;

            if ($purged === 0) {
                break;
            }
        }

        $this->info($total.' messages purged.');

        return self::SUCCESS;
    }
}
