<?php

namespace App\Services\Customers;

use App\Models\AccountDeletionRequest;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\BookingStatusEvent;
use App\Models\BookingAttachment;
use App\Models\BookingContact;
use App\Models\BookingItem;
use App\Models\BookingProof;
use App\Models\BookingStop;
use App\Models\DeliveryAttempt;
use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\CouponAttempt;
use App\Models\CouponReservation;
use App\Models\CustomerMessage;
use App\Models\SavedPlace;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\WalletStatementService;
use App\Repositories\Contracts\Payments\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Models\UserConsent;
use App\Models\UserDevice;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;

/**
 * Builds the machine-readable copy of everything the platform holds about one customer (data-subject access
 * request, SRS §16.2 / §13.3). Push tokens and secrets are never included. Modules that store customer data later
 * (payments, tickets, ratings) add their section here; until then `sections_pending` says so honestly.
 */
final class PersonalDataExporter
{
    public function __construct(
        private readonly CustomerProfileRepositoryInterface $profiles,
        private readonly UserDeviceRepositoryInterface $devices,
        private readonly UserConsentRepositoryInterface $consents,
        private readonly SavedPlaceRepositoryInterface $savedPlaces,
        private readonly AccountDeletionRequestRepositoryInterface $deletionRequests,
        private readonly CustomerMessageRepositoryInterface $messages,
        private readonly BookingRepositoryInterface $bookings,
        private readonly RatingRepositoryInterface $ratings,
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly BookingMessageRepositoryInterface $chat,
        private readonly BookingStatusEventRepositoryInterface $bookingEvents,
        private readonly PaymentMethodRepositoryInterface $paymentMethods,
        private readonly PaymentRepositoryInterface $payments,
        private readonly CouponReservationRepositoryInterface $couponReservations,
        private readonly CouponAttemptRepositoryInterface $couponAttempts,
        private readonly WalletStatementService $wallet,
        private readonly BookingContactRepositoryInterface $contacts,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly DeliveryAttemptRepositoryInterface $deliveryAttempts,
        private readonly BookingItemRepositoryInterface $items,
        private readonly BookingAttachmentRepositoryInterface $attachments,
        private readonly SafetyIncidentRepositoryInterface $safetyIncidents,
        private readonly TrustedContactRepositoryInterface $trustedContacts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $customer): array
    {
        $profile = $this->profiles->firstOrCreateFor($customer);

        return [
            'exported_at' => now()->toIso8601String(),
            'safety' => $this->safety($customer),
            'account' => [
                'uuid' => $customer->uuid,
                'name' => $customer->name,
                'phone' => $customer->phone_e164,
                'phone_verified_at' => $customer->phone_verified_at?->toIso8601String(),
                'email' => $customer->email,
                'preferred_locale' => $customer->preferred_locale,
                'status' => $customer->status->value,
                'registered_at' => $customer->created_at->toIso8601String(),
                'last_seen_at' => $customer->last_seen_at?->toIso8601String(),
            ],
            'profile' => [
                'accessibility_needs' => $profile->accessibility_needs,
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
                'marketing_opt_in' => $profile->marketing_opt_in,
                'marketing_opt_in_at' => $profile->marketing_opt_in_at?->toIso8601String(),
            ],
            'devices' => $this->devices->datatableQuery($customer)->get()->map(static fn (UserDevice $device): array => [
                'app' => $device->app->value,
                'platform' => $device->platform->value,
                'device_id' => $device->device_id,
                'app_version' => $device->app_version,
                'locale' => $device->locale,
                'last_active_at' => $device->last_active_at?->toIso8601String(),
            ])->all(),
            'consents' => array_map(static fn (UserConsent $consent): array => [
                'policy_type' => $consent->policyVersion->type->value,
                'policy_version' => $consent->policyVersion->version,
                'accepted_at' => $consent->accepted_at->toIso8601String(),
                'locale' => $consent->locale,
                'app' => $consent->app->value,
                'ip' => $consent->ip,
            ], $this->consents->forUser($customer)),
            'saved_places' => $this->savedPlaces->forUser($customer->id)->map(static fn (SavedPlace $place): array => [
                'label' => $place->label,
                'type' => $place->type->value,
                'lat' => $place->point->lat,
                'lng' => $place->point->lng,
                'address' => $place->address['line'] ?? null,
            ])->all(),
            'deletion_requests' => $this->deletionRequests->datatableQuery($customer)->get()->map(static fn (AccountDeletionRequest $request): array => [
                'status' => $request->status->value,
                'reason' => $request->reason,
                'requested_at' => $request->requested_at->toIso8601String(),
                'processed_at' => $request->processed_at?->toIso8601String(),
            ])->all(),
            'help_messages' => $this->messages->datatableQuery($customer)->get()->map(static fn (CustomerMessage $message): array => [
                'subject' => $message->subject,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
            ])->all(),
            'bookings' => $this->bookings->exportForCustomer($customer->id)->map(fn (Booking $booking): array => $this->booking($booking))->all(),
            // Display data only: brand and last four digits. The gateway token is never exported.
            'payment_methods' => $this->paymentMethods->activeForUser($customer->id)->map(static fn (PaymentMethod $method): array => [
                'type' => $method->type->value,
                'brand' => $method->brand,
                'last4' => $method->last4,
                'saved_at' => $method->created_at->toIso8601String(),
            ])->all(),
            'payments' => $this->payments->forCustomer($customer->id)->map(static fn (Payment $payment): array => [
                'id' => $payment->uuid,
                'booking' => $payment->booking->code,
                'kind' => $payment->kind->value,
                'method' => $payment->method_type->value,
                'status' => $payment->status->value,
                'amount_minor' => $payment->amount_minor,
                'refunded_minor' => $payment->refunded_minor,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at->toIso8601String(),
            ])->all(),
            // Coupon uses (reserved, committed, released, reversed) and every attempt the customer made, newest first.
            'coupon_usage' => $this->couponReservations->reservationsQuery((new CouponActivityFilterData)->forUser($customer->id))->latest('coupon_reservations.id')->get()->map(static fn (CouponReservation $reservation): array => [
                'coupon' => $reservation->coupon->code ?? $reservation->coupon->name,
                'booking' => $reservation->booking?->code,
                'status' => $reservation->status->value,
                'discount_minor' => $reservation->redemption?->discount_minor ?? $reservation->hold_minor,
                'final' => $reservation->redemption !== null,
                'currency' => $reservation->currency,
                'reserved_at' => $reservation->reserved_at->toIso8601String(),
            ])->all(),
            'coupon_attempts' => $this->couponAttempts->forUser($customer->id)->map(static fn (CouponAttempt $attempt): array => [
                'coupon' => $attempt->coupon?->code,
                'code_entered' => $attempt->code_entered,
                'context' => $attempt->context->value,
                'result' => $attempt->result->value,
                'reason' => $attempt->reason_code,
                'at' => $attempt->created_at->toIso8601String(),
            ])->all(),
            // The customer wallet: balances and every ledger line of it (credits from refunds / approved adjustments, wallet payments).
            'wallet' => $this->walletSection($customer),
            // Support tickets (reports and appeals included): the person's own messages and the public replies; internal notes are never exported.
            'support_tickets' => $this->tickets->allOfUser($customer->id)->map(static fn (SupportTicket $ticket): array => [
                'number' => $ticket->number,
                'app' => $ticket->app->value,
                'category' => $ticket->category->value,
                'status' => $ticket->status->value,
                'source' => $ticket->source->value,
                'subject' => $ticket->subject,
                'booking' => $ticket->booking?->code,
                'resolution' => $ticket->resolution_reason,
                'created_at' => $ticket->created_at->toIso8601String(),
                'messages' => $ticket->messages->map(static fn (TicketMessage $message): array => [
                    'from' => $message->author_type->value,
                    'body' => $message->body,
                    'at' => $message->created_at->toIso8601String(),
                ])->all(),
            ])->all(),
            // What the person wrote in booking chats (the other side's messages belong to the other person).
            'chat_messages' => $this->chat->sentBy($customer->id)->map(static fn (BookingMessage $message): array => [
                'booking' => $message->booking->code,
                'body' => $message->body,
                'has_image' => $message->attachment_path !== null,
                'at' => $message->created_at->toIso8601String(),
            ])->all(),
            'sections_pending' => ['ratings'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function walletSection(User $customer): array
    {
        $accounts = $this->wallet->customerAccounts($customer);

        return [
            'accounts' => $accounts->map(static fn (LedgerAccount $account): array => ['currency' => $account->currency, 'balance_minor' => $account->balance_minor])->values()->all(),
            'transactions' => $this->wallet->allLines($accounts)->map(static fn (JournalLine $line): array => [
                'type' => $line->entry->type->value,
                'change_minor' => $line->side === $line->account->type->normalSide() ? $line->amount_minor : -$line->amount_minor,
                'balance_after_minor' => $line->balance_after_minor,
                'currency' => $line->currency,
                'posted_at' => $line->entry->posted_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * What this customer wrote about the trip: their rating of the provider and their reports.
     *
     * @return array<string, mixed>
     */
    private function feedback(Booking $booking): array
    {
        $rating = $this->ratings->findForSide($booking->id, 'customer');
        return [
            'rating' => $rating === null ? null : ['stars' => $rating->stars, 'tags' => $rating->tags, 'comment' => $rating->comment],
        ];
    }

    /**
     * Parcel data of a booking (P4.1): the contacts the customer typed (third-party data they entered), proofs as facts (type,
     * stage, time, hash; the pictures are not part of the export), failed delivery attempts and the restricted-goods acknowledgement.
     *
     * @return array<string, mixed>|null
     */
    private function parcel(Booking $booking): ?array
    {
        $contacts = $this->contacts->forBooking($booking->id);
        $proofs = $this->proofs->forBooking($booking->id);
        $attempts = $this->deliveryAttempts->forBooking($booking->id);

        if ($contacts->isEmpty() && $proofs->isEmpty() && $attempts->isEmpty() && $booking->custody_status === null) {
            return null;
        }

        return [
            'custody_status' => $booking->custody_status?->value,
            'restricted_goods_acknowledged_at' => $booking->restricted_goods_acknowledged_at?->toIso8601String(),
            'contacts' => $contacts->map(static fn (BookingContact $contact): array => ['role' => $contact->role->value, 'name' => $contact->name(), 'phone' => $contact->phone()])->values()->all(),
            'proofs' => $proofs->map(static fn (BookingProof $proof): array => ['type' => $proof->proof_type->value, 'stage' => $proof->stage->value, 'taken_at' => $proof->created_at->toIso8601String(), 'sha256' => $proof->sha256])->values()->all(),
            'delivery_attempts' => $attempts->map(static fn (DeliveryAttempt $attempt): array => [
                'number' => $attempt->attempt_number,
                'outcome' => $attempt->outcome->value,
                'reason' => $attempt->reason,
                'resolution' => $attempt->resolution?->value,
                'at' => $attempt->created_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Moving / freight data of a booking (P4.2): the load lines, the requested and accepted scope, the pickup window, the contacts the
     * customer typed for intermediate stops and the attachments as facts (kind, size, time, hash; the files are not part of the export).
     *
     * @return array<string, mixed>|null
     */
    private function movingFreight(Booking $booking): ?array
    {
        $items = $this->items->forBooking($booking->id);
        $attachments = $this->attachments->forBooking($booking->id);

        if ($items->isEmpty() && $attachments->isEmpty() && $booking->requested_scope === null) {
            return null;
        }

        return [
            'requested_scope' => $booking->requested_scope,
            'agreed_scope' => $booking->agreed_scope,
            'weight_kg' => $booking->cargo_weight_kg,
            'volume_m3' => $booking->cargo_volume_m3,
            'pickup_window' => $booking->pickup_window_start === null ? null : ['start' => $booking->pickup_window_start->toIso8601String(), 'end' => $booking->pickup_window_end?->toIso8601String()],
            'items' => $items->map(static fn (BookingItem $item): array => ['kind' => $item->kind, 'code' => $item->code, 'label' => $item->label, 'quantity' => $item->quantity, 'volume_m3' => $item->volume_m3, 'weight_kg' => $item->weight_kg, 'note' => $item->note])->values()->all(),
            'stop_contacts' => $booking->stops->filter(static fn (BookingStop $stop): bool => $stop->contact_encrypted !== null)->map(static fn (BookingStop $stop): array => ['position' => $stop->position] + (array) $stop->contact())->values()->all(),
            'attachments' => $attachments->map(static fn (BookingAttachment $attachment): array => ['kind' => $attachment->kind, 'field' => $attachment->field, 'size_bytes' => $attachment->size_bytes, 'uploaded_at' => $attachment->created_at->toIso8601String(), 'sha256' => $attachment->sha256])->values()->all(),
        ];
    }

    private function booking(Booking $booking): array
    {
        return [
            'id' => $booking->uuid,
            'code' => $booking->code,
            'service' => $booking->service_snapshot['category']['name'] ?? null,
            'booking_type' => $booking->booking_type->value,
            'status' => $booking->status->value,
            'created_at' => $booking->created_at->toIso8601String(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'completed_at' => $booking->completed_at?->toIso8601String(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
            'pickup' => ['lat' => $booking->pickup_point->lat, 'lng' => $booking->pickup_point->lng, 'address' => $booking->pickup_address],
            'dropoff' => $booking->dropoff_point === null ? null : ['lat' => $booking->dropoff_point->lat, 'lng' => $booking->dropoff_point->lng, 'address' => $booking->dropoff_address],
            'stops' => $booking->stops->map(static fn (BookingStop $stop): array => ['position' => $stop->position, 'lat' => $stop->point->lat, 'lng' => $stop->point->lng, 'address' => $stop->address])->all(),
            'passengers' => $booking->passengers,
            'service_details' => $booking->service_details,
            'proposed_fare_minor' => $booking->proposed_fare_minor,
            'currency' => $booking->currency,
            'payment_method' => $booking->payment_method->value,
            'payment_status' => $booking->payment_status->value,
            'agreed_fare_minor' => $booking->agreed_fare_minor,
            'final_fare_minor' => $booking->final_fare_minor,
            'cancellation' => $booking->cancelled_at === null ? null : [
                'by' => $booking->cancelled_by_type,
                'reason_code' => $booking->cancellation_reason_code,
                'fee_minor' => $booking->cancellation_fee_minor,
            ],
            'ratings_and_reports' => $this->feedback($booking),
            'parcel' => $this->parcel($booking),
            'moving_freight' => $this->movingFreight($booking),
            'timeline' => $this->bookingEvents->forBooking($booking)->map(static fn (BookingStatusEvent $event): array => [
                'from' => $event->from_status?->value,
                'to' => $event->to_status->value,
                'actor' => $event->actor_type,
                'at' => $event->created_at->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Trusted contacts (with the phone, as the person entered it), the safety cases the person raised and what the platform did
     * towards the contacts. Staff notes are NOT part of it (internal); the person's own words and the public messages are.
     *
     * @return array<string, mixed>
     */
    private function safety(User $customer): array
    {
        return [
            'trusted_contacts' => $this->trustedContacts->forUser($customer->id)->map(static fn (\App\Models\TrustedContact $contact): array => [
                'name' => $contact->name,
                'phone' => $contact->phone(),
                'relationship' => $contact->relationship?->value,
                'notify_on_sos' => $contact->notify_on_sos,
                'share_trip' => $contact->share_trip,
                'share_location_on_sos' => $contact->share_location_on_sos,
                'consent_confirmed_at' => $contact->consent_confirmed_at->toIso8601String(),
            ])->all(),
            'incidents' => $this->safetyIncidents->allOfUser($customer->id)->map(static fn (\App\Models\SafetyIncident $incident): array => [
                'number' => $incident->number,
                'booking' => $incident->booking?->code,
                'trigger' => $incident->trigger->value,
                'category' => $incident->category->value,
                'status' => $incident->status->value,
                'occurred_at' => $incident->occurred_at->toIso8601String(),
                'narrative' => $incident->narrative,
                'outcome' => $incident->outcome?->value,
                'legal_hold' => $incident->legal_hold,
            ])->all(),
            'trusted_contact_actions' => $this->trustedContacts->actionsOfUser($customer->id)->map(static fn (\App\Models\TrustedContactAction $action): array => [
                'action' => $action->action->value,
                'status' => $action->status->value,
                'contact' => $action->contact_name,
                'at' => $action->created_at->toIso8601String(),
            ])->all(),
        ];
    }
}
