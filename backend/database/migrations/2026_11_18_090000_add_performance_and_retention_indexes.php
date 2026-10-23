<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P6.2: indexes for the admin booking listing (default order created_at desc, filtered by status / payment status) and for the
 * retention jobs, which delete by age and must not scan. Purely additive; reviewed by reading the hot queries (docs/PERFORMANCE.md).
 */
return new class extends Migration
{
    /** @var array<string, array<string, list<string>>> */
    private const INDEXES = [
        'bookings' => [
            'bookings_created_at_index' => ['created_at'],
            'bookings_status_created_at_index' => ['status', 'created_at'],
            'bookings_payment_status_created_at_index' => ['payment_status', 'created_at'],
            'bookings_booking_type_created_at_index' => ['booking_type', 'created_at'],
        ],
        'booking_offers' => ['booking_offers_status_created_at_index' => ['status', 'created_at']],
        'booking_share_links' => ['booking_share_links_expires_at_index' => ['expires_at']],
        'gateway_webhook_events' => ['gateway_webhook_events_signature_valid_received_at_index' => ['signature_valid', 'received_at']],
        'admin_sessions' => ['admin_sessions_last_active_at_index' => ['last_active_at']],
        'notification_deliveries' => ['notification_deliveries_created_at_index' => ['created_at']],
        'coupon_attempts' => ['coupon_attempts_created_at_index' => ['created_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes): void {
                foreach ($indexes as $name => $columns) {
                    if (! Schema::hasIndex($blueprint->getTable(), $name)) {
                        $blueprint->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes): void {
                foreach (array_keys($indexes) as $name) {
                    if (Schema::hasIndex($blueprint->getTable(), $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }
};
