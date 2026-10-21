<?php

namespace App\Console\Commands;

use App\Services\Location\LocationRetentionService;
use Illuminate\Console\Command;

/**
 * `locations:prune` (daily): applies the location history retention policy. Idempotent.
 */
class PruneProviderLocations extends Command
{
    protected $signature = 'locations:prune';

    protected $description = 'Delete provider location history past its retention period';

    public function handle(LocationRetentionService $retention): int
    {
        $this->info('Deleted rows: '.$retention->prune());

        return self::SUCCESS;
    }
}
