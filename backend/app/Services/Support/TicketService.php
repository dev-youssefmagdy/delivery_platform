<?php

namespace App\Services\Support;

use App\DTOs\Support\OpenTicketData;
use App\DTOs\Support\TicketReplyData;
use App\Enums\Booking\ReportCategory;
use App\Enums\Common\ActorType;
use App\Enums\Identity\ClientApp;
use App\Enums\Support\TicketCategory;
use App\Enums\Support\TicketEventType;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketSource;
use App\Enums\Support\TicketStatus;
use App\Enums\Support\TicketVisibility;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Repositories\Contracts\Support\TicketThreadRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Support tickets from the user's side (SRS §14.3): open (from the app, from a booking report, from a fee appeal), read the
 * public thread, reply with evidence. A person only ever sees their own tickets in the app they wrote from, never internal
 * notes, internal events or the assignee. Opening and replying never touch money: refunds and wallet adjustments are separate,
 * permissioned finance operations that staff only LINK to a ticket.
 */
final class TicketService
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $tickets,
        private readonly TicketThreadRepositoryInterface $thread,
        private readonly BookingRepositoryInterface $bookings,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly TicketAttachmentService $attachments,
        private readonly TicketAssigner $assigner,
        private readonly SlaCalculator $sla,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `ticket_category_not_allowed`, `ticket_limit_reached`, `ticket_rate_limited`, `ticket_link_invalid`
     */
    public function open(User $user, ClientApp $app, OpenTicketData $data): SupportTicket
    {
        if (! in_array($data->category, TicketCategory::forApp($app->value), true)) {
            throw BusinessRuleViolation::make('ticket_category_not_allowed', 'category');
        }

        $this->assertWithinLimits($user);
        [$bookingId, $paymentId, $payoutId] = $this->resolveLinks($user, $app, $data);
        $checked = $this->attachments->inspectAll($data->attachments);

        return $this->create($user, $app, $data->category, TicketSource::App, trim($data->subject), trim($data->body), $bookingId, $paymentId, $payoutId, null, $checked);
    }

    /**
     * "Report an issue" on a booking now creates a ticket (P2.5 report endpoint, P3.7).
     */
    public function openFromReport(Booking $booking, ActorType $reporter, User $user, ReportCategory $category, string $description): SupportTicket
    {
        $ticketCategory = $category->ticketCategory($reporter === ActorType::Provider);

        return $this->create(
            $user,
            $reporter === ActorType::Provider ? ClientApp::Provider : ClientApp::Customer,
            $ticketCategory,
            TicketSource::Report,
            $ticketCategory->label().' · '.$booking->code,
            trim($description),
            $booking->id,
            null,
            null,
            ['report_category' => $category->value],
            [],
        );
    }

    /**
     * Appeal of a cancellation fee or penalty: copies the fee decision snapshot of the booking into the ticket context so
     * support sees exactly what the customer or driver saw (P3.5). One appeal per booking and person.
     *
     * @throws BusinessRuleViolation `appeal_not_available`, `appeal_already_open`
     */
    public function openAppeal(User $user, ClientApp $app, string $bookingUuid, string $reason): SupportTicket
    {
        $booking = $this->bookingFor($user, $app, $bookingUuid);
        $decision = (array) ($booking->cancellation_decision ?? []);

        $chargedToMe = (int) ($booking->cancellation_fee_minor ?? 0) > 0 && ($decision['payer'] ?? null) === $app->value;
        $penalised = $app === ClientApp::Provider && ! empty($decision['penalty']);

        if (! $booking->status->isCancellation() || (! $chargedToMe && ! $penalised)) {
            throw BusinessRuleViolation::make('appeal_not_available');
        }

        if ($this->tickets->findForBookingAndSource($booking->id, $user->id, TicketSource::Appeal->value) !== null) {
            throw BusinessRuleViolation::make('appeal_already_open');
        }

        $this->assertWithinLimits($user);

        return $this->create(
            $user,
            $app,
            TicketCategory::FareExtraCharge,
            TicketSource::Appeal,
            __('api.support.appeal_subject', ['code' => $booking->code]),
            trim($reason),
            $booking->id,
            null,
            null,
            ['appeal' => ['fee_minor' => $booking->cancellation_fee_minor, 'currency' => $booking->currency, 'decision' => $decision, 'status' => $booking->status->value]],
            [],
            TicketCategory::FareExtraCharge->defaultPriority(),
        );
    }

    /**
     * @return Builder<SupportTicket>
     */
    public function queryFor(User $user, ClientApp $app, ?string $status): Builder
    {
        return $this->tickets->userQuery($user->id, $app, $status);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findFor(User $user, ClientApp $app, string $uuid): SupportTicket
    {
        return $this->tickets->findOfUser($uuid, $user->id, $app) ?? throw (new ModelNotFoundException)->setModel(SupportTicket::class);
    }

    /**
     * The thread a person may see: public messages only.
     *
     * @return Collection<int, TicketMessage>
     */
    public function publicThread(SupportTicket $ticket): Collection
    {
        return $this->thread->messages($ticket, false);
    }

    /**
     * @throws BusinessRuleViolation `ticket_closed`, `ticket_empty_reply`
     */
    public function reply(User $user, ClientApp $app, string $uuid, TicketReplyData $data): TicketMessage
    {
        $body = trim($data->body);

        if ($body === '') {
            throw BusinessRuleViolation::make('ticket_empty_reply', 'body');
        }

        $checked = $this->attachments->inspectAll($data->attachments);
        $paths = [];

        try {
            return $this->db->transaction(function () use ($user, $app, $uuid, $body, $checked, &$paths): TicketMessage {
                $ticket = $this->lockOwn($user, $app, $uuid);
                $before = $ticket->status;

                if ($ticket->status === TicketStatus::Closed || ($ticket->status === TicketStatus::Resolved && ! $this->withinReopenWindow($ticket))) {
                    throw BusinessRuleViolation::make('ticket_closed');
                }

                $message = $this->thread->createMessage([
                    'ticket_id' => $ticket->id,
                    'visibility' => TicketVisibility::Public,
                    'author_type' => $app === ClientApp::Provider ? ActorType::Provider : ActorType::Customer,
                    'author_user_id' => $user->id,
                    'body' => $body,
                ]);
                $paths = $this->attachments->store($ticket, $message->id, $checked);

                $attributes = ['last_message_at' => now()];

                if ($ticket->status === TicketStatus::Resolved) {
                    $attributes += ['status' => TicketStatus::Open, 'resolved_at' => null, 'resolution_reason' => null];
                } elseif ($ticket->status === TicketStatus::WaitingUser) {
                    $attributes['status'] = TicketStatus::InProgress;
                }

                $this->tickets->update($ticket, $attributes);

                if ($before === TicketStatus::Resolved) {
                    $this->thread->recordEvent($ticket, TicketEventType::Reopened->value, $app->value, $user->id, ['by' => 'user_reply']);
                } elseif ($before === TicketStatus::WaitingUser) {
                    $this->thread->recordEvent($ticket, TicketEventType::StatusChanged->value, $app->value, $user->id, ['from' => $before->value, 'to' => TicketStatus::InProgress->value]);
                }

                $this->thread->recordEvent($ticket, TicketEventType::Reply->value, $app->value, $user->id, ['message' => $message->uuid]);

                return $message->load('attachments');
            });
        } catch (Throwable $e) {
            $this->attachments->discard($paths);

            throw $e;
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    public function attachmentFor(User $user, ClientApp $app, string $ticketUuid, string $attachmentUuid): StreamedResponse
    {
        $ticket = $this->findFor($user, $app, $ticketUuid);
        $attachment = $this->thread->findAttachment($ticket, $attachmentUuid, false);

        if (! $attachment instanceof TicketAttachment || ! $this->attachments->exists($attachment)) {
            throw (new ModelNotFoundException)->setModel(TicketAttachment::class);
        }

        return $this->attachments->serve($attachment);
    }

    /**
     * @param array<string, mixed>|null $context
     * @param list<array{upload: \App\Support\Files\InspectedUpload, scan: \App\Enums\Provider\FileScanStatus}> $checked
     */
    private function create(
        User $user,
        ClientApp $app,
        TicketCategory $category,
        TicketSource $source,
        string $subject,
        string $body,
        ?int $bookingId,
        ?int $paymentId,
        ?int $payoutId,
        ?array $context,
        array $checked,
        ?TicketPriority $priority = null,
    ): SupportTicket {
        $priority ??= $category->defaultPriority();
        $group = $category->defaultGroup();
        $paths = [];

        try {
            return $this->db->transaction(function () use ($user, $app, $category, $source, $subject, $body, $bookingId, $paymentId, $payoutId, $context, $checked, $priority, $group, &$paths): SupportTicket {
                $agent = $this->assigner->pick($group);
                $now = CarbonImmutable::now();
                $ticket = $this->tickets->createNumbered([
                    'user_id' => $user->id,
                    'app' => $app,
                    'category' => $category,
                    'priority' => $priority,
                    'status' => TicketStatus::Open,
                    'source' => $source,
                    'subject' => mb_substr($subject, 0, (int) config('support.tickets.subject_max_chars', 160)),
                    'booking_id' => $bookingId,
                    'payment_id' => $paymentId,
                    'payout_request_id' => $payoutId,
                    'group' => $group,
                    'assigned_admin_id' => $agent?->id,
                    'assigned_at' => $agent === null ? null : $now,
                    'sla_due_at' => $this->sla->dueAt($priority, $now),
                    'last_message_at' => $now,
                    'context' => $context,
                ]);
                $message = $this->thread->createMessage([
                    'ticket_id' => $ticket->id,
                    'visibility' => TicketVisibility::Public,
                    'author_type' => $app === ClientApp::Provider ? ActorType::Provider : ActorType::Customer,
                    'author_user_id' => $user->id,
                    'body' => $body,
                ]);
                $paths = $this->attachments->store($ticket, $message->id, $checked);
                $this->thread->recordEvent($ticket, TicketEventType::Created->value, $app->value, $user->id, ['source' => $source->value, 'category' => $category->value, 'priority' => $priority->value]);

                if ($agent !== null) {
                    $this->thread->recordEvent($ticket, TicketEventType::Assigned->value, ActorType::System->value, null, ['admin_id' => $agent->id, 'rule' => 'least_loaded']);
                }

                return $ticket;
            });
        } catch (Throwable $e) {
            $this->attachments->discard($paths);

            throw $e;
        }
    }

    private function assertWithinLimits(User $user): void
    {
        if ($this->tickets->countActiveOfUser($user->id) >= (int) config('support.tickets.max_open_per_user', 20)) {
            throw BusinessRuleViolation::make('ticket_limit_reached', context: ['max' => (int) config('support.tickets.max_open_per_user', 20)]);
        }

        if ($this->tickets->countCreatedSince($user->id, CarbonImmutable::now()->subDay()) >= (int) config('support.tickets.new_per_day', 10)) {
            throw BusinessRuleViolation::make('ticket_rate_limited', context: ['max' => (int) config('support.tickets.new_per_day', 10)]);
        }
    }

    /**
     * Resolves the public ids the client sent to internal ids, only for things the person owns.
     *
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function resolveLinks(User $user, ClientApp $app, OpenTicketData $data): array
    {
        $bookingId = $data->bookingUuid === null ? null : $this->bookingFor($user, $app, $data->bookingUuid)->id;
        $paymentId = null;
        $payoutId = null;

        if ($data->paymentUuid !== null) {
            $paymentId = $this->tickets->resolveOwnedPaymentId($user->id, $data->paymentUuid) ?? throw BusinessRuleViolation::make('ticket_link_invalid', 'payment_id');
        }

        if ($data->payoutUuid !== null) {
            $profile = $this->profileOf($user);
            $payoutId = $profile === null ? null : $this->tickets->resolveOwnedPayoutId($profile->id, $data->payoutUuid);

            if ($payoutId === null) {
                throw BusinessRuleViolation::make('ticket_link_invalid', 'payout_id');
            }
        }

        return [$bookingId, $paymentId, $payoutId];
    }

    private function bookingFor(User $user, ClientApp $app, string $uuid): Booking
    {
        if ($app === ClientApp::Customer) {
            $booking = $this->bookings->findOfCustomer($uuid, $user->id);
        } else {
            $profile = $this->profileOf($user);
            $booking = $profile === null ? null : $this->bookings->findAssignedToProvider($uuid, $profile->id);
        }

        return $booking ?? throw BusinessRuleViolation::make('ticket_link_invalid', 'booking_id');
    }

    private function profileOf(User $user): ?ProviderProfile
    {
        return $this->providers->findByUserId($user->id);
    }

    private function lockOwn(User $user, ClientApp $app, string $uuid): SupportTicket
    {
        $ticket = $this->findFor($user, $app, $uuid);

        return $this->tickets->lockById($ticket->id) ?? throw (new ModelNotFoundException)->setModel(SupportTicket::class);
    }

    private function withinReopenWindow(SupportTicket $ticket): bool
    {
        return $ticket->resolved_at !== null && $ticket->resolved_at->addDays((int) config('support.tickets.reopen_days', 7))->isFuture();
    }
}
