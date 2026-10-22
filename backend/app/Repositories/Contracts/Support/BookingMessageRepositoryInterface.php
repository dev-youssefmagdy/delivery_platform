<?php

namespace App\Repositories\Contracts\Support;

use App\Enums\Common\ActorType;
use App\Models\BookingMessage;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Every query on `booking_messages` (chat).
 *
 * @extends BaseRepositoryInterface<BookingMessage>
 */
interface BookingMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * A page of a booking's conversation, oldest first. `$afterId` returns newer messages (polling / reconnect), `$beforeId`
     * older ones (scrolling back); with neither, the latest `$limit`.
     *
     * @return Collection<int, BookingMessage>
     */
    public function page(int $bookingId, ?int $afterId, ?int $beforeId, int $limit): Collection;

    public function findInBooking(int $bookingId, string $uuid): ?BookingMessage;

    public function idOfUuid(int $bookingId, string $uuid): ?int;

    public function unreadCount(int $bookingId, ActorType $recipient): int;

    /**
     * Read receipt: marks the other side's unread messages up to `$upToId` as read. Returns how many changed.
     */
    public function markRead(int $bookingId, ActorType $reader, ?int $upToId, CarbonInterface $at): int;

    /**
     * @return Collection<int, BookingMessage> the whole conversation (staff transcript, export)
     */
    public function allForBooking(int $bookingId): Collection;

    /**
     * Messages of bookings that ended before `$cutoff`, not yet purged, without an active ticket on the booking.
     *
     * @return Collection<int, BookingMessage>
     */
    public function purgeable(CarbonInterface $cutoff, int $limit): Collection;

    public function markPurged(BookingMessage $message): void;

    /**
     * Removes the text and the attachment reference of everything the user wrote (account deletion). Returns the
     * attachment paths to delete from disk.
     *
     * @return list<string>
     */
    public function scrubSentBy(int $userId): array;

    /**
     * @return Collection<int, BookingMessage> what the user wrote in chats, for the personal data export
     */
    public function sentBy(int $userId): Collection;
}
