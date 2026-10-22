<?php

namespace App\Services\Booking;

use App\Enums\Catalog\BookingType;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingAttachment;
use App\Models\BookingContact;
use App\Models\BookingProof;
use App\Models\BookingStatusEvent;
use App\Models\DeliveryAttempt;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Courier\DeliveryAttemptService;
use App\Services\Scope\BookingLoadPresenter;
use App\Services\Scope\OfferScopeService;
use App\Support\Auth\PhoneNumber;
use App\Support\Files\PrivateFileStorage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The "Delivery and load" tab of a booking (P4.4, SRS §16.6): contacts (masked, audited reveal), items, requested vs accepted scope,
 * custody timeline, proofs and attachments (signed URLs; every file view is audited) and the failed delivery attempts.
 * Contacts, free text and pictures are personal data: they need `bookings.view_pii`, and nothing leaves here in clear text
 * except through `revealContacts`, which writes an audit entry per call.
 */
final class BookingDeliveryAdminService
{
    public function __construct(
        private readonly BookingContactRepositoryInterface $contacts,
        private readonly BookingProofRepositoryInterface $proofs,
        private readonly BookingAttachmentRepositoryInterface $attachments,
        private readonly BookingStatusEventRepositoryInterface $events,
        private readonly DeliveryAttemptRepositoryInterface $attempts,
        private readonly BookingLoadPresenter $loads,
        private readonly OfferScopeService $scopes,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Whether the booking has anything for this tab (it is hidden for rides).
     */
    public function applies(Booking $booking): bool
    {
        return in_array($booking->booking_type, [BookingType::Parcel, BookingType::Freight], true) || ($booking->requested_scope ?? []) !== [] || ($booking->agreed_scope ?? []) !== []
            || $this->contacts->forBooking($booking->id)->isNotEmpty() || $this->loads->load($booking) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Booking $booking, bool $canViewPii): array
    {
        $load = $this->loads->load($booking);

        if ($load !== null && ! $canViewPii) {
            foreach ($load['items'] as &$item) {
                $item['label'] = $item['kind'] === 'free' ? null : $item['label'];
                $item['note'] = null;
            }

            unset($item);
        }

        return [
            'contacts' => $this->contactRows($booking),
            'load' => $load,
            'scope' => $this->scopeComparison($booking),
            'custody' => $this->custody($booking),
            'attempts' => $this->attemptRows($booking, $canViewPii),
            'proofs' => $this->proofRows($booking, $canViewPii),
            'attachments' => $this->attachmentRows($booking, $canViewPii),
            'can_view_files' => $canViewPii,
        ];
    }

    /**
     * Every field of the contacts in clear text, keyed as the masked placeholders of the tab. One audit entry per call: which
     * fields, never the values.
     *
     * @return array<string, string>
     */
    public function revealContacts(Booking $booking, Admin $actor): array
    {
        $values = [];

        foreach ($this->contacts->forBooking($booking->id) as $contact) {
            $values['name_'.$contact->role->value] = (string) $contact->name();
            $values['phone_'.$contact->role->value] = (string) $contact->phone();
        }

        foreach ($booking->stops as $stop) {
            $contact = $stop->contact();

            if ($contact !== null) {
                $values['name_stop_'.$stop->position] = $contact['name'];
                $values['phone_stop_'.$stop->position] = $contact['phone'];
                $values['note_stop_'.$stop->position] = (string) ($contact['note'] ?? '');
            }
        }

        $this->audit->record(AuditAction::PhoneRevealed, $booking, $actor, meta: ['scope' => 'booking_contacts', 'fields' => array_keys($values)]);

        return $values;
    }

    /**
     * Streams a proof picture to an admin who may see personal data; every view is audited.
     */
    public function streamProof(Booking $booking, string $proofUuid, Admin $actor): StreamedResponse
    {
        $proof = $this->proofs->findInBooking($booking->id, $proofUuid);

        if (! $proof instanceof BookingProof || $proof->storage_path === null || ! $this->storage->exists($proof->storage_path)) {
            throw (new ModelNotFoundException)->setModel(BookingProof::class);
        }

        $this->audit->record(AuditAction::Viewed, $booking, $actor, meta: ['file' => 'proof', 'proof' => $proof->uuid, 'proof_type' => $proof->proof_type->value, 'stage' => $proof->stage->value]);

        return $this->storage->response($proof->storage_path, (string) ($proof->mime ?? 'image/jpeg'), false, $proof->uuid.'.jpg');
    }

    /**
     * Streams a request attachment (photo, video, document) to an admin who may see personal data; every view is audited.
     */
    public function streamAttachment(Booking $booking, string $attachmentUuid, Admin $actor): StreamedResponse
    {
        $attachment = $this->attachments->findInBooking($booking->id, $attachmentUuid);

        if (! $attachment instanceof BookingAttachment || ! $this->storage->exists($attachment->storage_path)) {
            throw (new ModelNotFoundException)->setModel(BookingAttachment::class);
        }

        $this->audit->record(AuditAction::Viewed, $booking, $actor, meta: ['file' => 'attachment', 'attachment' => $attachment->uuid, 'kind' => $attachment->kind]);
        $extension = pathinfo((string) $attachment->storage_path, PATHINFO_EXTENSION);

        return $this->storage->response((string) $attachment->storage_path, $attachment->mime, false, $attachment->uuid.'.'.$extension);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contactRows(Booking $booking): array
    {
        $rows = $this->contacts->forBooking($booking->id)->map(static fn (BookingContact $contact): array => [
            'key' => $contact->role->value,
            'role' => $contact->role->label(),
            'name' => $contact->name() === null ? null : mb_substr((string) $contact->name(), 0, 1).'•••',
            'phone' => $contact->phone() === null ? null : PhoneNumber::mask((string) $contact->phone()),
            'note' => null,
            'name_key' => 'name_'.$contact->role->value,
            'phone_key' => 'phone_'.$contact->role->value,
            'note_key' => null,
        ])->values()->all();

        foreach ($booking->stops as $stop) {
            $contact = $stop->contact();

            if ($contact === null) {
                continue;
            }

            $rows[] = [
                'key' => 'stop_'.$stop->position,
                'role' => (string) __('admin.bookings.delivery.stop_contact', ['position' => $stop->position]),
                'name' => mb_substr($contact['name'], 0, 1).'•••',
                'phone' => PhoneNumber::mask($contact['phone']),
                'note' => ($contact['note'] ?? '') === '' ? null : '•••',
                'name_key' => 'name_stop_'.$stop->position,
                'phone_key' => 'phone_stop_'.$stop->position,
                'note_key' => 'note_stop_'.$stop->position,
            ];
        }

        return $rows;
    }

    /**
     * The requested and accepted scope side by side, with the same translated lines the apps show; a line is "changed" when the
     * accepted value differs from the request (null side = not set).
     *
     * @return array{rows: list<array{label: string, requested: string|null, agreed: string|null, changed: bool}>, has_requested: bool, has_agreed: bool}|null
     */
    private function scopeComparison(Booking $booking): ?array
    {
        $requested = collect($this->scopes->summary($booking->requested_scope))->keyBy('key');
        $agreed = collect($this->scopes->summary($booking->agreed_scope))->keyBy('key');

        if ($requested->isEmpty() && $agreed->isEmpty()) {
            return null;
        }

        $rows = [];

        foreach ($requested->keys()->merge($agreed->keys())->unique() as $key) {
            $left = $requested->get($key)['value'] ?? null;
            $right = $agreed->get($key)['value'] ?? null;
            $rows[] = [
                'label' => (string) ($requested->get($key)['label'] ?? $agreed->get($key)['label']),
                'requested' => $left,
                'agreed' => $right,
                'changed' => $left !== null && $right !== null && $left !== $right,
            ];
        }

        return ['rows' => $rows, 'has_requested' => $requested->isNotEmpty(), 'has_agreed' => $agreed->isNotEmpty()];
    }

    /**
     * @return array{status: string|null, events: list<array{title: string, actor: string, time: string}>}
     */
    private function custody(Booking $booking): array
    {
        $events = $this->events->forBooking($booking)
            ->filter(static fn (BookingStatusEvent $event): bool => $event->from_status === $event->to_status && is_string($event->reason)
                && (str_starts_with($event->reason, 'custody_') || str_starts_with($event->reason, 'delivery_')))
            ->map(fn (BookingStatusEvent $event): array => [
                'title' => trans()->has("admin.bookings.timeline.events.{$event->reason}") ? (string) __("admin.bookings.timeline.events.{$event->reason}") : (string) __('admin.bookings.timeline.note'),
                'actor' => ActorType::tryFrom($event->actor_type)?->label() ?? $event->actor_type,
                'time' => $this->time($event->created_at),
            ])->values()->all();

        return ['status' => $booking->custody_status?->label(), 'events' => $events];
    }

    /**
     * @return array{items: list<array<string, mixed>>, count: int, max: int, pending: bool}
     */
    private function attemptRows(Booking $booking, bool $canViewPii): array
    {
        $items = $this->attempts->forBooking($booking->id);

        return [
            'items' => $items->map(fn (DeliveryAttempt $attempt): array => [
                'number' => $attempt->attempt_number,
                'outcome' => $attempt->outcome->label(),
                'reason' => $canViewPii ? $attempt->reason : null,
                'resolution' => $attempt->resolution?->label(),
                'resolved_by' => $attempt->resolved_by_type === null ? null : (ActorType::tryFrom($attempt->resolved_by_type)?->label() ?? $attempt->resolved_by_type),
                'time' => $this->time($attempt->created_at),
                'resolved_at' => $attempt->resolved_at === null ? null : $this->time($attempt->resolved_at),
            ])->values()->all(),
            'count' => $booking->delivery_attempts_count,
            'max' => DeliveryAttemptService::maxAttempts(),
            'pending' => $items->contains(static fn (DeliveryAttempt $attempt): bool => $attempt->isPending()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function proofRows(Booking $booking, bool $canViewPii): array
    {
        return $this->proofs->forBooking($booking->id)->map(function (BookingProof $proof) use ($booking, $canViewPii): array {
            $metadata = (array) $proof->metadata;
            $hasFile = $proof->storage_path !== null && $this->storage->exists($proof->storage_path);

            return [
                'type' => $proof->proof_type->label(),
                'stage' => $proof->stage->label(),
                'actor' => ActorType::tryFrom($proof->actor_type)?->label() ?? $proof->actor_type,
                'time' => $this->time($proof->created_at),
                'has_file' => $hasFile,
                'file_removed' => $proof->storage_path === null && in_array($proof->proof_type->value, ['photo', 'signature'], true),
                'url' => $hasFile && $canViewPii ? $this->signed('admin.bookings.files.proof', $booking, ['proof' => $proof->uuid]) : null,
                'weight_kg' => $metadata['weight_kg'] ?? null,
                'weight_mismatch' => (bool) ($metadata['weight_mismatch'] ?? false),
                'checklist' => $proof->proof_type->value === 'checklist' ? array_keys(array_filter((array) ($metadata['items'] ?? []))) : null,
                'checklist_total' => $proof->proof_type->value === 'checklist' ? count((array) ($metadata['items'] ?? [])) : null,
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attachmentRows(Booking $booking, bool $canViewPii): array
    {
        return $this->attachments->forBooking($booking->id)->map(fn (BookingAttachment $attachment): array => [
            'kind' => $attachment->kind,
            'mime' => $attachment->mime,
            'size_kb' => (int) ceil($attachment->size_bytes / 1024),
            'time' => $this->time($attachment->created_at),
            'url' => $canViewPii && $this->storage->exists($attachment->storage_path) ? $this->signed('admin.bookings.files.attachment', $booking, ['attachment' => $attachment->uuid]) : null,
        ])->values()->all();
    }

    /**
     * @param array<string, string> $parameters
     */
    private function signed(string $route, Booking $booking, array $parameters): string
    {
        return URL::temporarySignedRoute($route, now()->addMinutes((int) config('courier.proof_url_ttl_minutes', 5)), ['booking' => $booking->uuid] + $parameters);
    }

    private function time(CarbonInterface $at): string
    {
        return $at->copy()->setTimezone((string) config('admin.display_timezone', 'UTC'))->locale(app()->getLocale())->translatedFormat('Y-m-d H:i');
    }
}
