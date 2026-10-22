<?php

namespace App\Services\Booking;

use App\DTOs\Finance\PriceBreakdown;
use App\Enums\Booking\BookingStatus;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Models\BookingAdjustmentRequest;
use App\Models\BookingReceipt;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingReceiptRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Support\Money\CurrencyRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Receipts (SRS §6.7, Plan I4, P3.5). A receipt is issued once, when the trip is completed, from what the booking stored
 * then: the agreed fare, the approved adjustments, the price breakdown, the parties and the policy in force. It is never
 * rebuilt, so later edits of fares, commission rules, cancellation policies or names cannot change it (the table refuses
 * updates). A booking completed before receipts existed gets one on first read, marked `backfilled`, built only from its
 * stored breakdown. Tips are separate payments made after completion and are not part of the receipt.
 *
 * The snapshot holds a customer section and a provider section; each API view returns only its own party's figures
 * (a customer never sees commission, a provider never sees the customer's fee, tax or discount funding).
 */
final class BookingReceiptService
{
    public function __construct(
        private readonly BookingReceiptRepositoryInterface $receipts,
        private readonly BookingAdjustmentRepositoryInterface $adjustments,
        private readonly BookingRepositoryInterface $bookings,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * Issues the receipt of a completed booking (idempotent: the first receipt wins).
     *
     * @throws ConflictException `receipt_not_available` when the booking is not completed or has no price breakdown
     */
    public function issue(Booking $booking, bool $backfilled = false): BookingReceipt
    {
        $existing = $this->receipts->findForBooking($booking->id);

        if ($existing instanceof BookingReceipt) {
            return $existing;
        }

        if (! in_array($booking->status, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment], true) || $booking->price_breakdown === null) {
            throw ConflictException::make('receipt_not_available');
        }

        $snapshot = $this->snapshot($booking, $backfilled);

        try {
            return $this->receipts->firstOrCreate($booking->id, [
                'uuid' => (string) Str::uuid(),
                'number' => 'RC-'.$booking->code,
                'kind' => 'trip',
                'currency' => $booking->currency,
                'total_minor' => (int) $snapshot['customer']['total_minor'],
                'snapshot' => $snapshot,
                'issued_at' => CarbonImmutable::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->receipts->findForBooking($booking->id) ?? throw ConflictException::make('receipt_not_available');
        }
    }

    public function forCustomer(User $customer, string $bookingUuid): BookingReceipt
    {
        return $this->forBooking($this->bookings->findOfCustomer($bookingUuid, $customer->id));
    }

    public function forProvider(ProviderProfile $provider, string $bookingUuid): BookingReceipt
    {
        return $this->forBooking($this->bookings->findAssignedToProvider($bookingUuid, $provider->id));
    }

    /**
     * Receipt of a booking for the admin booking page: the stored one, or null (never created lazily by a read of staff).
     */
    public function existing(Booking $booking): ?BookingReceipt
    {
        return $this->receipts->findForBooking($booking->id);
    }

    /**
     * What the customer sees.
     *
     * @return array<string, mixed>
     */
    public function customerView(BookingReceipt $receipt): array
    {
        $s = $receipt->snapshot;

        return $this->envelope($receipt) + [
            'booking' => $s['booking'],
            'parties' => ['provider' => $s['parties']['provider'] ?? null],
            'payment' => $s['payment'],
            'fare' => $s['customer'],
            'policy' => $s['policy'] ?? null,
        ];
    }

    /**
     * What the provider sees on the job receipt: the fare they earned on, never the customer's fee, tax or coupon funding.
     *
     * @return array<string, mixed>
     */
    public function providerView(BookingReceipt $receipt): array
    {
        $s = $receipt->snapshot;

        return $this->envelope($receipt) + [
            'booking' => $s['booking'],
            'parties' => ['customer' => ['name' => $s['parties']['customer']['name'] ?? null]],
            'payment' => $s['payment'],
            'fare' => [
                'agreed_minor' => $s['customer']['agreed_minor'],
                'adjustments' => $s['customer']['adjustments'],
                'adjustments_total_minor' => $s['customer']['adjustments_total_minor'],
                'subtotal_minor' => $s['customer']['subtotal_minor'],
            ],
            'earnings' => $s['provider'],
            'policy' => $s['policy'] ?? null,
        ];
    }

    private function forBooking(?Booking $booking): BookingReceipt
    {
        if (! $booking instanceof Booking) {
            throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        return $this->receipts->findForBooking($booking->id) ?? $this->issue($booking->loadMissing(['customer', 'provider.user']), backfilled: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(BookingReceipt $receipt): array
    {
        return [
            'uuid' => $receipt->uuid,
            'number' => $receipt->number,
            'kind' => $receipt->kind,
            'currency' => $receipt->currency,
            'total_minor' => $receipt->total_minor,
            'issued_at' => $receipt->issued_at->toIso8601ZuluString(),
            'backfilled' => (bool) ($receipt->snapshot['backfilled'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Booking $booking, bool $backfilled): array
    {
        $booking->loadMissing(['customer', 'provider.user']);
        $breakdown = PriceBreakdown::fromArray((array) $booking->price_breakdown, $this->currencies->get($booking->currency));
        $approved = $this->adjustments->approvedForBooking($booking->id);
        $adjustmentsTotal = $booking->adjustments_total_minor;
        $category = (array) ($booking->service_snapshot['category']['name'] ?? []);
        $policy = (array) ($booking->policy_snapshot['cancellation'] ?? []);

        return [
            'version' => 1,
            'backfilled' => $backfilled,
            'booking' => [
                'uuid' => $booking->uuid,
                'code' => $booking->code,
                'service' => $category,
                'type' => $booking->booking_type->value,
                'pickup' => (string) ($booking->pickup_address['line'] ?? ''),
                'dropoff' => (string) ($booking->dropoff_address['line'] ?? ''),
                'distance_m' => $booking->route_snapshot['distance_m'] ?? null,
                'started_at' => $booking->started_at?->toIso8601ZuluString(),
                'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            ],
            'parties' => [
                'customer' => ['name' => $booking->customer->name],
                'provider' => ['name' => $booking->provider?->user->name],
            ],
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'customer' => [
                'agreed_minor' => (int) $booking->agreed_fare_minor,
                'adjustments' => $approved->map(static fn (BookingAdjustmentRequest $adjustment): array => [
                    'uuid' => $adjustment->uuid,
                    'type' => $adjustment->type->value,
                    'reason' => $adjustment->reason,
                    'amount_minor' => $adjustment->amount_minor,
                    'approved_at' => $adjustment->decided_at?->toIso8601ZuluString(),
                ])->values()->all(),
                'adjustments_total_minor' => $adjustmentsTotal,
                'subtotal_minor' => $breakdown->agreedFare->amount,
                'discount_minor' => $breakdown->discount->amount,
                'customer_fee_minor' => $breakdown->customerFee->amount,
                'tax_rate_bp' => $breakdown->taxRateBp,
                'tax_minor' => $breakdown->tax->amount,
                'tip_minor' => $breakdown->tip->amount,
                'total_minor' => $breakdown->customerPayable->amount,
            ],
            'provider' => [
                'gross_minor' => $breakdown->providerGross->amount,
                'commission_minor' => $breakdown->commission->amount,
                'discount_provider_minor' => $breakdown->discountProvider->amount,
                'net_minor' => $breakdown->providerNet->amount,
                'earning_minor' => $breakdown->providerEarning->amount,
            ],
            'policy' => [
                'cancellation' => array_filter([
                    'id' => $policy['id'] ?? null,
                    'name' => $policy['name'] ?? null,
                    'free_grace_seconds' => $policy['free_grace_seconds'] ?? null,
                    'free_wait_seconds' => $policy['free_wait_seconds'] ?? null,
                ], static fn (mixed $value): bool => $value !== null),
            ],
        ];
    }
}
