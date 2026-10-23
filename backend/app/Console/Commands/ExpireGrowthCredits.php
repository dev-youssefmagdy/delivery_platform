<?php

namespace App\Console\Commands;

use App\Services\Growth\CreditExpiryService;
use Illuminate\Console\Command;

class ExpireGrowthCredits extends Command
{
    protected $signature = 'growth:expire-credits';

    protected $description = 'Expire referral and loyalty credits whose validity ended and take the unspent part back through the ledger';

    public function handle(CreditExpiryService $expiry): int
    {
        $closed = $expiry->run(now(), (int) config('growth.expiry_batch', 200));
        $this->info("Credits closed: {$closed}");

        return self::SUCCESS;
    }
}
