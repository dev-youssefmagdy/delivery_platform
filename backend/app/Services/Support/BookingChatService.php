<?php

namespace App\Services\Support;

use App\DTOs\Notification\NotificationMessage;
use App\DTOs\Support\ChatMessageData;
use App\DTOs\Support\ChatPage;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Events\Realtime\BookingMessageReceived;
use App\Events\Realtime\BookingMessagesRead;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ForbiddenActionException;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\NotificationService;
use App\Support\Chat\ChatWindow;
use App\Support\Chat\PhoneMasker;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Booking chat (SRS §14.2): a text/image conversation between the booking's customer and its SELECTED provider, and nobody
 * else. Authorization is decided here from the signed-in account and the booking row (never from the client): a person who is
 * not a participant gets 403 `chat_forbidden`; sending after the window closed gets 403 `chat_closed`. Reading stays possible
 * for participants until the retention job removes the messages. Images are inspected, re-encoded, scanned and kept on the
 * private disk, served through signed routes. Realtime and push carry no message text. Staff reading a conversation is audited.
 */
final class BookingChatService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly ProviderProfileRepositoryInterface $providers,
        private readonly BookingMessageRepositoryInterface $messages,
        private readonly UploadInspector $inspector,
        private readonly UploadScanner $scanner,
        private readonly PrivateFileStorage $storage,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ForbiddenActionException `chat_forbidden`
     */
    public function list(User $user, ClientApp $app, string $bookingUuid, ?string $afterUuid, ?string $beforeUuid): ChatPage
    {
        [$booking, $role] = $this->participant($user, $app, $bookingUuid);
        $limit = (int) config('support.chat.page_size', 50);
        $afterId = $afterUuid === null ? null : $this->messages->idOfUuid($booking->id, $afterUuid);
        $beforeId = $beforeUuid === null ? null : $this->messages->idOfUuid($booking->id, $beforeUuid);
        $page = $this->messages->page($booking->id, $afterId, $beforeId, $limit + 1);
        $hasMore = $afterId === null && $page->count() > $limit;

        if ($hasMore) {
            $page = $page->slice(1)->values();
        }

        return $this->pageOf($booking, $role, $page, $hasMore);
    }

    /**
     * @throws ForbiddenActionException `chat_forbidden`, `chat_closed`
     * @throws BusinessRuleViolation `chat_empty`, `chat_message_too_long`, attachment errors
     */
    public function send(User $user, ClientApp $app, string $bookingUuid, ChatMessageData $data): BookingMessage
    {
        [$booking, $role] = $this->participant($user, $app, $bookingUuid);

        if (! ChatWindow::isOpen($booking)) {
            throw ForbiddenActionException::make('chat_closed');
        }

        $body = $data->body === null ? null : trim($data->body);
        $body = $body === '' ? null : $body;

        if ($body === null && $data->attachment === null) {
            throw BusinessRuleViolation::make('chat_empty', 'body');
        }

        if ($body !== null && mb_strlen($body) > (int) config('support.chat.max_chars', 1000)) {
            throw BusinessRuleViolation::make('chat_message_too_long', 'body', ['max' => (int) config('support.chat.max_chars', 1000)]);
        }

        $path = null;
        $attachment = [];

        if ($data->attachment !== null) {
            [$path, $attachment] = $this->storeAttachment($booking, $data);
        }

        try {
            $message = $this->db->transaction(fn (): BookingMessage => $this->messages->create([
                'booking_id' => $booking->id,
                'sender_type' => $role,
                'sender_user_id' => $user->id,
                'body' => $body === null ? null : PhoneMasker::mask($body),
            ] + $attachment));
        } catch (Throwable $e) {
            $this->storage->delete($path);

            throw $e;
        }

        $this->announce($booking, $message, $role);

        return $message;
    }

    /**
     * Read receipt: everything the other side wrote up to now (or up to `$upToUuid`) is marked read.
     */
    public function markRead(User $user, ClientApp $app, string $bookingUuid, ?string $upToUuid): int
    {
        [$booking, $role] = $this->participant($user, $app, $bookingUuid);
        $upToId = $upToUuid === null ? null : $this->messages->idOfUuid($booking->id, $upToUuid);
        $changed = $this->messages->markRead($booking->id, $role, $upToId, CarbonImmutable::now());

        if ($changed > 0) {
            $this->emitToSide($booking, $role === ActorType::Customer ? ActorType::Provider : ActorType::Customer, BookingMessagesRead::class, ['reader' => $role->value, 'read_count' => $changed]);
        }

        return $changed;
    }

    /**
     * @throws ForbiddenActionException `chat_forbidden`
     * @throws ModelNotFoundException
     */
    public function attachmentOf(User $user, ClientApp $app, string $bookingUuid, string $messageUuid): StreamedResponse
    {
        [$booking] = $this->participant($user, $app, $bookingUuid);
        $message = $this->messages->findInBooking($booking->id, $messageUuid);

        if ($message === null || ! $message->hasAttachment() || ! $this->storage->exists($message->attachment_path)) {
            throw (new ModelNotFoundException)->setModel(BookingMessage::class);
        }

        return $this->storage->response((string) $message->attachment_path, (string) $message->attachment_mime, false, $message->uuid.'.jpg');
    }

    /**
     * Staff access to a conversation (dispute handling). Needs `support.view_all`-level access decided by the caller's policy and
     * is audited with the reason on every read.
     *
     * @return Collection<int, BookingMessage>
     */
    public function staffTranscript(Booking $booking, Admin $admin, ?string $reason = null): Collection
    {
        $this->audit->record(AuditAction::Viewed, $booking, $admin, meta: ['resource' => 'booking_chat', 'booking' => $booking->uuid], reason: $reason);

        return $this->messages->allForBooking($booking->id);
    }

    /**
     * Retention job: removes the text and images of conversations older than the retention period, except while a ticket
     * about the booking is still open (the audit trail for disputes).
     */
    public function purgeExpired(int $batch = 500): int
    {
        $cutoff = CarbonImmutable::now()->subDays((int) config('support.chat.retention_days', 180));
        $purged = 0;

        foreach ($this->messages->purgeable($cutoff, $batch) as $message) {
            $path = $message->attachment_path;
            $this->messages->markPurged($message);
            $this->storage->delete($path);
            $purged++;
        }

        return $purged;
    }

    /**
     * @return array{0: Booking, 1: ActorType}
     */
    private function participant(User $user, ClientApp $app, string $bookingUuid): array
    {
        $booking = $this->bookings->findByUuid($bookingUuid);

        if (! $booking instanceof Booking) {
            throw (new ModelNotFoundException)->setModel(Booking::class);
        }

        $role = null;

        if ($user->status->blockCode() === null && ! $user->trashed()) {
            if ($app === ClientApp::Customer && $booking->customer_id === $user->id) {
                $role = ActorType::Customer;
            } elseif ($app === ClientApp::Provider && $booking->provider_id !== null) {
                $profile = $this->providers->findByUserId($user->id);
                $role = $profile !== null && $profile->id === $booking->provider_id ? ActorType::Provider : null;
            }
        }

        if ($role === null || ! ChatWindow::hasConversation($booking)) {
            throw ForbiddenActionException::make('chat_forbidden');
        }

        return [$booking, $role];
    }

    /**
     * @param Collection<int, BookingMessage> $page
     */
    private function pageOf(Booking $booking, ActorType $role, Collection $page, bool $hasMore): ChatPage
    {
        return new ChatPage(
            $page,
            $role,
            $booking->uuid,
            ChatWindow::isOpen($booking),
            ChatWindow::closesAt($booking),
            $this->messages->unreadCount($booking->id, $role),
            $hasMore,
        );
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function storeAttachment(Booking $booking, ChatMessageData $data): array
    {
        $file = $data->attachment;
        $inspected = $this->inspector->inspectPhoto($file, (int) config('support.chat.attachment_max_kilobytes', 5120), (int) config('support.chat.attachment_max_edge', 1600));

        if (! in_array($inspected->mime, (array) config('support.chat.attachment_mimes', []), true)) {
            throw BusinessRuleViolation::make('document_type_not_allowed', 'attachment');
        }

        try {
            $scan = $this->scanner->scan((string) $file->getRealPath());
        } catch (FileScannerUnavailableException) {
            throw ServiceUnavailableException::make('file_scanner_unavailable', 'attachment');
        }

        if ($scan->isInfected()) {
            throw BusinessRuleViolation::make('document_infected', 'attachment');
        }

        $path = $this->storage->putChatAttachment($booking->uuid, $inspected);

        return [$path, [
            'attachment_path' => $path,
            'attachment_mime' => $inspected->mime,
            'attachment_size' => $inspected->size(),
            'attachment_hash' => $inspected->hash(),
            'attachment_scan_status' => $scan->status,
        ]];
    }

    /**
     * Realtime hint and push to the other side. Failures here never undo a stored message.
     */
    private function announce(Booking $booking, BookingMessage $message, ActorType $sender): void
    {
        $recipient = $sender === ActorType::Customer ? ActorType::Provider : ActorType::Customer;
        $unread = $this->messages->unreadCount($booking->id, $recipient);

        try {
            $this->emitToSide($booking, $recipient, BookingMessageReceived::class, [
                'message_uuid' => $message->uuid,
                'sender' => $sender->value,
                'has_attachment' => $message->attachment_path !== null,
                'unread' => $unread,
            ]);

            $booking->loadMissing('customer', 'provider.user');
            $user = $recipient === ActorType::Customer ? $booking->customer : $booking->provider?->user;

            if ($user instanceof User) {
                $this->notifications->send(new NotificationMessage(
                    NotificationEvent::BookingMessageReceived,
                    $user,
                    $recipient === ActorType::Customer ? ClientApp::Customer : ClientApp::Provider,
                    ['code' => $booking->code],
                    $booking->uuid,
                    'chat:'.$message->uuid,
                    ['collapse_key' => 'chat:'.$booking->uuid],
                ));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param class-string<BookingMessageReceived|BookingMessagesRead> $event
     * @param array<string, mixed> $payload
     */
    private function emitToSide(Booking $booking, ActorType $side, string $event, array $payload): void
    {
        if ($side === ActorType::Customer) {
            $booking->loadMissing('customer');
            event($event::toCustomer($booking->customer->uuid, $booking->uuid, $booking->sequence, $payload));

            return;
        }

        $booking->loadMissing('provider');

        if ($booking->provider !== null) {
            event($event::toProvider($booking->provider->uuid, $booking->uuid, $booking->sequence, $payload));
        }
    }
}
