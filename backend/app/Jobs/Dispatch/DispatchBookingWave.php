<?php

namespace App\Jobs\Dispatch;

use App\Enums\Common\QueueName;
use App\Services\Dispatch\DispatchableRequestSource;
use App\Services\Dispatch\DispatchService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One dispatch wave of a booking (critical queue). The request is re-read each time, so a booking that was assigned,
 * cancelled, whose search window ended or whose request changed since the job was queued (another generation) simply
 * stops the chain. Unique per booking, generation and wave: a retry or a duplicate push cannot schedule the next wave twice.
 */
class DispatchBookingWave implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds before attempts 2 and 3 (short: these are latency sensitive) */
    public array $backoff = [2, 10];

    public int $uniqueFor = 600;

    public function __construct(public readonly int $bookingId, public readonly int $wave = 1, public readonly int $generation = 1)
    {
        $this->onQueue(QueueName::Critical->value);
    }

    public function uniqueId(): string
    {
        return "dispatch-wave:{$this->bookingId}:{$this->generation}:{$this->wave}";
    }

    public function handle(DispatchableRequestSource $source, DispatchService $dispatch): void
    {
        $request = $source->find($this->bookingId);

        if ($request === null || $request->generation !== $this->generation) {
            return;
        }

        $dispatch->runWave($request, $this->wave);
    }
}
