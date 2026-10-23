<?php

namespace App\Jobs\Booking;

use App\Enums\Common\QueueName;
use App\Services\Offers\OfferExpiryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Delayed to the exact end of one offer's validity (critical queue). `offers:expire` is the backstop for a lost job.
 * The job closes the offer only if it is still active and really past its expiry (a revision extends it, and an
 * older job then finds nothing to do); running it twice changes nothing. Acceptance never depends on this job: the
 * selection transaction checks the expiry itself (Plan I2).
 */
class ExpireOffer implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds before attempts 2 and 3 (short: these are latency sensitive) */
    public array $backoff = [2, 10];

    public function __construct(public readonly int $offerId)
    {
        $this->onQueue(QueueName::Critical->value);
    }

    public function handle(OfferExpiryService $expiry): void
    {
        $expiry->expire($this->offerId);
    }
}
