<?php

namespace App\Console\Commands;

use App\DTOs\Booking\SubmitOfferData;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\ProviderProfile;
use App\Services\Offers\OfferSubmissionService;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

/**
 * `qa:race-offer {booking} {provider}`: manual concurrency proof for offer submission. N real processes submit
 * counter-offers for the same provider and booking at once. The booking lock serialises them, so the provider ends
 * with ONE active offer whose revisions never exceed the booking's limit (surplus requests get 422). With
 * `--repeat-key` every worker sends the same Idempotency-Key and amount: exactly one version may exist.
 * Needs a dispatched provider (a `booking_dispatch_recipients` row), MySQL and a local environment.
 */
class QaRaceOffer extends Command
{
    protected $signature = 'qa:race-offer {booking : booking uuid} {provider : provider profile uuid} {--workers=8} {--repeat-key} {--amount= : base counter amount in minor units (default: proposed fare + 1%)} {--worker= : internal: amount} {--key= : internal: idempotency key}';

    protected $description = 'QA: N parallel counter-offers from one provider; limits and idempotency must hold';

    public function handle(OfferSubmissionService $submission): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('qa:race-offer runs only in local or testing environments.');

            return self::FAILURE;
        }

        $booking = Booking::query()->where('uuid', (string) $this->argument('booking'))->first();
        $provider = ProviderProfile::query()->where('uuid', (string) $this->argument('provider'))->first();

        if (! $booking instanceof Booking || ! $provider instanceof ProviderProfile) {
            $this->error('Booking or provider not found.');

            return self::FAILURE;
        }

        if ($this->option('worker') !== null) {
            try {
                $result = $submission->counterOffer($provider, $booking->uuid, new SubmitOfferData(amountMinor: (int) $this->option('worker'), idempotencyKey: (string) $this->option('key')));
                $this->line((string) json_encode(['ok' => true, 'replayed' => $result->replayed, 'code' => null]));
            } catch (DomainException $exception) {
                $this->line((string) json_encode(['ok' => false, 'replayed' => false, 'code' => $exception->errorCode]));
            } catch (Throwable $exception) {
                $this->line((string) json_encode(['ok' => false, 'replayed' => false, 'code' => 'error: '.$exception::class]));
            }

            return self::SUCCESS;
        }

        $workers = max(2, (int) $this->option('workers'));
        $base = (int) ($this->option('amount') ?: (int) ceil($booking->proposed_fare_minor * 1.01));
        $repeat = (bool) $this->option('repeat-key');
        $sharedKey = (string) Str::uuid();

        $results = Process::pool(function (Pool $pool) use ($workers, $booking, $provider, $base, $repeat, $sharedKey): void {
            for ($i = 0; $i < $workers; $i++) {
                $pool->as((string) $i)->path(base_path())->command([
                    PHP_BINARY, 'artisan', 'qa:race-offer', $booking->uuid, $provider->uuid,
                    '--worker='.($repeat ? $base : $base + $i), '--key='.($repeat ? $sharedKey : Str::uuid()),
                ]);
            }
        })->start()->wait();

        $accepted = 0;

        foreach ($results as $name => $result) {
            $outcome = json_decode(trim($result->output()), true) ?: ['ok' => false, 'code' => 'no output: '.trim($result->errorOutput())];
            $accepted += $outcome['ok'] ? 1 : 0;
            $this->line(sprintf('worker %s: %s', $name, $outcome['ok'] ? ($outcome['replayed'] ? 'replay' : 'applied') : $outcome['code']));
        }

        $offers = BookingOffer::query()->where('booking_id', $booking->id)->where('provider_profile_id', $provider->id)->get();
        $versions = $offers->isEmpty() ? 0 : (int) \App\Models\BookingOfferVersion::query()->whereIn('offer_id', $offers->pluck('id'))->count();
        $limit = 1 + (int) ($booking->policy_snapshot['offers']['max_revisions'] ?? 0);
        $ok = $offers->count() <= max(1, (int) ($booking->policy_snapshot['offers']['max_per_provider'] ?? 1)) && $versions <= $limit * max(1, $offers->count()) && ($repeat ? $versions === 1 : $versions >= 1);

        $this->line(sprintf('offers: %d, versions: %d (limit per offer: %d), requests that succeeded: %d', $offers->count(), $versions, $limit, $accepted));
        $this->line($ok ? 'PASS: limits and idempotency held.' : 'FAIL: limits or idempotency were broken.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
