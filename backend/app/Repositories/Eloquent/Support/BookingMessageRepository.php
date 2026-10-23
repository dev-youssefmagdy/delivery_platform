<?php

namespace App\Repositories\Eloquent\Support;

use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Support\TicketStatus;
use App\Models\BookingMessage;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<BookingMessage>
 */
final class BookingMessageRepository extends BaseRepository implements BookingMessageRepositoryInterface
{
    public function __construct(BookingMessage $model)
    {
        parent::__construct($model);
    }

    public function page(int $bookingId, ?int $afterId, ?int $beforeId, int $limit): Collection
    {
        $query = $this->query()->where('booking_id', $bookingId);

        if ($afterId !== null) {
            return $query->where('id', '>', $afterId)->orderBy('id')->limit($limit)->get()->toBase();
        }

        if ($beforeId !== null) {
            $query->where('id', '<', $beforeId);
        }

        return $query->orderByDesc('id')->limit($limit)->get()->reverse()->values()->toBase();
    }

    public function findInBooking(int $bookingId, string $uuid): ?BookingMessage
    {
        return $this->query()->where('booking_id', $bookingId)->where('uuid', $uuid)->first();
    }

    public function idOfUuid(int $bookingId, string $uuid): ?int
    {
        $id = $this->query()->where('booking_id', $bookingId)->where('uuid', $uuid)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function unreadCount(int $bookingId, ActorType $recipient): int
    {
        return $this->query()->where('booking_id', $bookingId)->where('sender_type', '!=', $recipient->value)->whereNull('read_at')->count();
    }

    public function markRead(int $bookingId, ActorType $reader, ?int $upToId, CarbonInterface $at): int
    {
        return $this->query()
            ->where('booking_id', $bookingId)
            ->where('sender_type', '!=', $reader->value)
            ->whereNull('read_at')
            ->when($upToId !== null, static fn ($query) => $query->where('id', '<=', $upToId))
            ->update(['read_at' => $at]);
    }

    public function allForBooking(int $bookingId): Collection
    {
        return $this->query()->where('booking_id', $bookingId)->orderBy('id')->get()->toBase();
    }

    public function purgeable(CarbonInterface $cutoff, int $limit): Collection
    {
        return $this->query()
            ->whereNull('purged_at')
            ->whereIn('booking_id', DB::table('bookings')
                ->select('id')
                ->whereIn('status', array_map(static fn (BookingStatus $status): string => $status->value, array_filter(BookingStatus::cases(), static fn (BookingStatus $status): bool => $status->isFinal())))
                ->whereRaw('COALESCE(completed_at, cancelled_at, updated_at) < ?', [$cutoff]))
            ->whereNotIn('booking_id', DB::table('support_tickets')
                ->select('booking_id')
                ->whereNotNull('booking_id')
                ->whereIn('status', array_map(static fn (TicketStatus $status): string => $status->value, TicketStatus::activeCases())))
            ->whereNotIn('booking_id', DB::table('safety_incidents')->select('booking_id')->where('legal_hold', true)->whereNotNull('booking_id'))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->toBase();
    }

    public function markPurged(BookingMessage $message): void
    {
        $message->forceFill(['body' => null, 'attachment_path' => null, 'attachment_hash' => null, 'purged_at' => now()])->save();
    }

    public function scrubSentBy(int $userId): array
    {
        $paths = $this->query()->where('sender_user_id', $userId)->whereNotNull('attachment_path')->pluck('attachment_path')->map(static fn (mixed $path): string => (string) $path)->all();
        $this->query()->where('sender_user_id', $userId)->update([
            'body' => null, 'attachment_path' => null, 'attachment_hash' => null, 'purged_at' => now(), 'updated_at' => now(),
        ]);

        return array_values($paths);
    }

    public function sentBy(int $userId): Collection
    {
        return $this->query()->with('booking:id,uuid,code')->where('sender_user_id', $userId)->whereNull('purged_at')->orderBy('id')->get()->toBase();
    }
}
