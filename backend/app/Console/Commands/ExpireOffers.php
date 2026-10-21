<?php

namespace App\Console\Commands;

use App\Services\Offers\OfferExpiryService;
use Illuminate\Console\Command;

/**
 * `offers:expire` (every 5 s): closes offers whose validity ended. The delayed `ExpireOffer` job does the same at the
 * exact second; both are idempotent, and acceptance never relies on either (the selection checks the expiry itself).
 */
class ExpireOffers extends Command
{
    protected $signature = 'offers:expire';

    protected $description = 'Expire booking offers that ran out of time';

    public function handle(OfferExpiryService $expiry): int
    {
        $this->info('Expired: '.$expiry->sweep());

        return self::SUCCESS;
    }
}
