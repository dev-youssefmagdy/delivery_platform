<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\BookingAttachment;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookingAttachmentRepository implements BookingAttachmentRepositoryInterface
{
    public function insert(array $attributes): BookingAttachment
    {
        return BookingAttachment::query()->create($attributes);
    }

    public function findOfCustomer(int $customerId, string $uuid): ?BookingAttachment
    {
        return BookingAttachment::query()->where('customer_id', $customerId)->where('uuid', $uuid)->first();
    }

    public function findInBooking(int $bookingId, string $uuid): ?BookingAttachment
    {
        return BookingAttachment::query()->where('booking_id', $bookingId)->where('uuid', $uuid)->first();
    }

    public function lockOwned(int $customerId, array $uuids): Collection
    {
        return BookingAttachment::query()->where('customer_id', $customerId)->whereIn('uuid', $uuids)->lockForUpdate()->get();
    }

    public function forBooking(int $bookingId): Collection
    {
        return BookingAttachment::query()->where('booking_id', $bookingId)->orderBy('id')->get();
    }

    public function countUnattached(int $customerId): int
    {
        return BookingAttachment::query()->where('customer_id', $customerId)->whereNull('booking_id')->count();
    }

    public function countForBooking(int $bookingId, string $kind): int
    {
        return BookingAttachment::query()->where('booking_id', $bookingId)->where('kind', $kind)->count();
    }

    public function update(BookingAttachment $attachment, array $attributes): void
    {
        $attachment->forceFill($attributes)->save();
    }

    public function delete(BookingAttachment $attachment): void
    {
        $attachment->delete();
    }

    public function eraseForCustomer(int $customerId): array
    {
        $paths = DB::table('booking_attachments')->where('customer_id', $customerId)->whereNotNull('storage_path')->pluck('storage_path')->map(strval(...))->all();
        DB::table('booking_attachments')->where('customer_id', $customerId)->delete();

        return array_values($paths);
    }
}
