<?php

namespace App\Console\Commands;

use App\Services\Retention\RetentionService;
use Illuminate\Console\Command;

/**
 * `retention:run` (daily): age-based clean-up of share links, webhook rows and payloads, login events, admin sessions,
 * notification deliveries and coupon attempts, sparing rows tied to a legal hold or an open ticket. Idempotent. Locations, chat,
 * OTP, idempotency keys, inbox and exports have their own jobs (see docs/PERFORMANCE.md).
 */
class RunRetention extends Command
{
    protected $signature = 'retention:run {--dry-run : Count what would be removed without changing anything} {--only=* : Limit to these steps}';

    protected $description = 'Apply the data retention schedule';

    public function handle(RetentionService $retention): int
    {
        $only = $this->option('only') === [] ? null : array_values(array_map('strval', (array) $this->option('only')));
        $dry = (bool) $this->option('dry-run');

        foreach ($retention->run($dry, $only) as $step => $rows) {
            $this->line(sprintf('%-24s %d %s', $step, $rows, $dry ? 'would be affected' : 'affected'));
        }

        return self::SUCCESS;
    }
}
