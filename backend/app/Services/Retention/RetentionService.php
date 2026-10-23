<?php

namespace App\Services\Retention;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Age-based clean-up for the tables that only need to live a while (`retention:run`, daily). Every step is a bounded, chunked
 * DELETE (or payload blanking) by age, so it is idempotent and safe to rerun or to run concurrently.
 *
 * Legal hold: rows that belong to a booking with a legal-hold safety incident or an active support ticket are skipped
 * ({@see self::heldBookingIds()}). Nothing here touches the ledger, journal, payments, refunds, payouts or bookings.
 */
final class RetentionService
{
    /** @var list<string> */
    public const STEPS = ['share_links', 'webhook_rejected', 'webhook_payloads', 'login_events', 'admin_sessions', 'notification_deliveries', 'coupon_attempts'];

    /**
     * @param list<string>|null $only
     * @return array<string, int> rows affected per step (what would be affected when $dryRun)
     */
    public function run(bool $dryRun = false, ?array $only = null): array
    {
        $result = [];

        foreach (self::STEPS as $step) {
            if ($only !== null && ! in_array($step, $only, true)) {
                continue;
            }

            $result[$step] = $this->{lcfirst(str_replace('_', '', ucwords($step, '_')))}($dryRun);
        }

        return $result;
    }

    private function shareLinks(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.share_links_days'));

        $query = fn (): Builder => DB::table('booking_share_links')
            ->where(static fn (Builder $q) => $q->where('expires_at', '<', $before)->orWhere('revoked_at', '<', $before))
            ->whereNotIn('booking_id', $this->heldBookingIds());

        return $this->delete($query, $dryRun);
    }

    private function webhookRejected(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.webhook_rejected_days'));

        return $this->delete(fn (): Builder => DB::table('gateway_webhook_events')->where('signature_valid', false)->where('received_at', '<', $before), $dryRun);
    }

    /**
     * Blank the raw payload of accepted events; the row (hash, ids, outcome) stays as payment audit trail. Events of a held
     * booking's payments keep their payload.
     */
    private function webhookPayloads(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.webhook_payload_days'));
        $held = DB::table('payments')->select('id')->whereIn('booking_id', $this->heldBookingIds());

        $query = static fn (): Builder => DB::table('gateway_webhook_events')
            ->where('signature_valid', true)
            ->whereNotNull('payload')
            ->where('received_at', '<', $before)
            ->where(static fn (Builder $q) => $q->whereNull('payment_id')->orWhereNotIn('payment_id', $held));

        if ($dryRun) {
            return $query()->count();
        }

        $total = 0;

        for ($i = 0; $i < (int) config('retention.max_chunks_per_step'); $i++) {
            $ids = $query()->orderBy('id')->limit((int) config('retention.chunk'))->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $total += DB::table('gateway_webhook_events')->whereIn('id', $ids)->update(['payload' => null]);
        }

        return $total;
    }

    private function loginEvents(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.login_events_days'));

        return $this->delete(fn (): Builder => DB::table('admin_login_events')->where('created_at', '<', $before), $dryRun);
    }

    private function adminSessions(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.admin_sessions_days'));

        return $this->delete(fn (): Builder => DB::table('admin_sessions')->where(static fn (Builder $q) => $q->where('last_active_at', '<', $before)->orWhere(static fn (Builder $n) => $n->whereNull('last_active_at')->where('created_at', '<', $before))), $dryRun);
    }

    private function notificationDeliveries(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.notification_deliveries_days'));

        // Rows still queued / sending belong to the retry sweep, whatever their age.
        return $this->delete(fn (): Builder => DB::table('notification_deliveries')->where('created_at', '<', $before)->whereNotIn('status', ['queued', 'sending']), $dryRun);
    }

    private function couponAttempts(bool $dryRun): int
    {
        $before = now()->subDays((int) config('retention.coupon_attempts_days'));

        return $this->delete(fn (): Builder => DB::table('coupon_attempts')->where('created_at', '<', $before), $dryRun);
    }

    /**
     * Booking ids that must keep their side data: a safety incident under legal hold, or a support ticket still open.
     */
    private function heldBookingIds(): Builder
    {
        $active = array_map(static fn ($case): string => $case->value, \App\Enums\Support\TicketStatus::activeCases());

        return DB::table('safety_incidents')->select('booking_id')->where('legal_hold', true)->whereNotNull('booking_id')
            ->union(DB::table('support_tickets')->select('booking_id')->whereNotNull('booking_id')->whereIn('status', $active));
    }

    /**
     * @param callable(): Builder $query a fresh query each call (chunk loop)
     */
    private function delete(callable $query, bool $dryRun): int
    {
        if ($dryRun) {
            return $query()->count();
        }

        $total = 0;
        $chunk = (int) config('retention.chunk');

        for ($i = 0; $i < (int) config('retention.max_chunks_per_step'); $i++) {
            $ids = $query()->orderBy('id')->limit($chunk)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            // Delete by primary key: the age predicate (with its sub-selects) is evaluated only by the SELECT above.
            $total += DB::table($query()->from)->whereIn('id', $ids)->delete();
        }

        return $total;
    }
}
