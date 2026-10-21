<?php

namespace App\Console\Commands;

use App\Enums\Booking\OfferStatus;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Services\Offers\OfferSelectionService;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

/**
 * `qa:race-accept {booking}`: manual concurrency proof (Plan §2.3, I1). Starts N real PHP processes that all try to
 * choose an offer of the same booking at the same moment (different offers, round robin) and checks that exactly one
 * of them won and the booking has exactly one provider. Needs a booking in `offers_received` with two or more active
 * offers, MySQL, and a local environment. `--repeat-key` makes every worker use the same Idempotency-Key and offer
 * (duplicate taps): all of them must report the same outcome.
 */
class QaRaceAccept extends Command
{
    protected $signature = 'qa:race-accept {booking : booking uuid} {--workers=8 : parallel processes} {--repeat-key : every worker repeats the same request} {--worker= : internal: offer uuid} {--key= : internal: idempotency key}';

    protected $description = 'QA: N parallel processes choose offers of one booking; exactly one must win';

    public function handle(OfferSelectionService $selection): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('qa:race-accept runs only in local or testing environments.');

            return self::FAILURE;
        }

        $booking = Booking::query()->where('uuid', (string) $this->argument('booking'))->first();

        if (! $booking instanceof Booking) {
            $this->error('Booking not found.');

            return self::FAILURE;
        }

        if ($this->option('worker') !== null) {
            return $this->work($selection, $booking);
        }

        return $this->race($booking);
    }

    private function work(OfferSelectionService $selection, Booking $booking): int
    {
        try {
            $result = $selection->accept($booking->customer, $booking->uuid, (string) $this->option('worker'), (string) $this->option('key'));
            $this->line((string) json_encode(['won' => ! $result->replayed, 'replayed' => $result->replayed, 'code' => null]));
        } catch (DomainException $exception) {
            $this->line((string) json_encode(['won' => false, 'replayed' => false, 'code' => $exception->errorCode]));
        } catch (Throwable $exception) {
            $this->line((string) json_encode(['won' => false, 'replayed' => false, 'code' => 'error: '.$exception::class]));
        }

        return self::SUCCESS;
    }

    private function race(Booking $booking): int
    {
        $offers = $booking->offers()->whereIn('status', OfferStatus::activeValues())->get();

        if ($offers->count() < ($this->option('repeat-key') ? 1 : 2)) {
            $this->error('The booking needs at least two active offers (one with --repeat-key).');

            return self::FAILURE;
        }

        $workers = max(2, (int) $this->option('workers'));
        $sharedKey = (string) Str::uuid();
        $repeat = (bool) $this->option('repeat-key');

        $results = Process::pool(function (Pool $pool) use ($workers, $offers, $booking, $repeat, $sharedKey): void {
            for ($i = 0; $i < $workers; $i++) {
                /** @var BookingOffer $offer */
                $offer = $repeat ? $offers->first() : $offers[$i % $offers->count()];
                $pool->as((string) $i)->path(base_path())->command([
                    PHP_BINARY, 'artisan', 'qa:race-accept', $booking->uuid,
                    '--worker='.$offer->uuid, '--key='.($repeat ? $sharedKey : Str::uuid()),
                ]);
            }
        })->start()->wait();

        $outcomes = [];

        foreach ($results as $name => $result) {
            $outcomes[(int) $name] = json_decode(trim($result->output()), true) ?: ['won' => false, 'code' => 'no output: '.trim($result->errorOutput())];
        }

        ksort($outcomes);

        foreach ($outcomes as $i => $outcome) {
            $this->line(sprintf('worker %d: %s', $i, $outcome['won'] ? 'WON' : ($outcome['replayed'] ?? false ? 'replay of the winner' : ($outcome['code'] ?? '?'))));
        }

        $booking->refresh();
        $winners = BookingOffer::query()->where('booking_id', $booking->id)->where('status', OfferStatus::AcceptedByCustomer->value)->count();
        $won = count(array_filter($outcomes, static fn (array $outcome): bool => $outcome['won']));
        $ok = $won === 1 && $winners === 1 && $booking->provider_id !== null && $booking->selected_offer_id !== null;

        $this->line(sprintf('winning workers: %d, accepted offers in DB: %d, booking status: %s', $won, $winners, $booking->status->value));
        $this->line($ok ? 'PASS: exactly one winner.' : 'FAIL: expected exactly one winner.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
