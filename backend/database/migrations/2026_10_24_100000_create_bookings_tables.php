<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // Human-friendly public reference ("BK-7H3K9Q"); also what support and receipts quote.
            $table->string('code', 16)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->restrictOnDelete();
            // The immutable published version the booking was priced and validated against (Plan I4).
            $table->foreignId('service_category_version_id')->constrained('service_category_versions')->restrictOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('service_zone_id')->nullable()->constrained('service_zones')->nullOnDelete();
            $table->foreignId('fare_policy_id')->nullable()->constrained('fare_policies')->restrictOnDelete();
            $table->string('booking_type', 24);
            // Written only by BookingTransitionService (ADR-16).
            $table->string('status', 32);
            // Last realtime / status-event sequence number issued for this booking (clients detect gaps and refetch).
            $table->unsignedInteger('sequence')->default(0);
            // Bumped by every change of the request while searching; dispatch jobs of an older generation stop.
            $table->unsignedInteger('request_version')->default(1);

            $table->geometry('pickup_point', 'point', 4326);
            $table->json('pickup_address')->nullable();
            $table->geometry('dropoff_point', 'point', 4326)->nullable();
            $table->json('dropoff_address')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedSmallInteger('passengers')->nullable();
            $table->unsignedSmallInteger('luggage_count')->nullable();
            $table->decimal('cargo_weight_kg', 10, 2)->nullable();
            // Validated against the version's form schema; the searchable parts are copied to the columns above.
            $table->json('service_details');

            $table->unsignedBigInteger('proposed_fare_minor');
            $table->char('currency', 3);
            $table->json('reference_estimate')->nullable();
            $table->json('route_snapshot')->nullable();
            $table->json('service_snapshot');
            $table->json('policy_snapshot');
            // Filled by the money core (P3.1) when the fare is agreed; nullable until then.
            $table->json('commission_snapshot')->nullable();

            $table->string('payment_method', 24);
            $table->string('payment_status', 32);
            // Plain ids until their tables exist: coupons (P3.3) and offers (P2.4) add the foreign keys.
            $table->unsignedBigInteger('coupon_reservation_id')->nullable();
            $table->unsignedBigInteger('selected_offer_id')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('provider_profiles')->nullOnDelete();

            $table->timestamp('search_expires_at')->nullable();
            // P2.5: the trip PIN is stored hashed (verification) and encrypted (shown to the customer).
            $table->string('pin_hash')->nullable();
            $table->text('pin_encrypted')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by_type', 16)->nullable();
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('idempotency_key', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'idempotency_key'], 'bookings_customer_idempotency_unique');
            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'search_expires_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['service_category_id', 'status']);
            $table->index(['service_zone_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index(['city_id', 'status', 'created_at']);
            $table->spatialIndex('pickup_point');
        });

        Schema::create('booking_stops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->geometry('point', 'point', 4326);
            $table->json('address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'position']);
        });

        // Append-only outbox of every status change (and request annotations: from = to). The sequence is the same
        // counter the realtime events use, so a timeline and a websocket stream can be reconciled.
        Schema::create('booking_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sequence');
            $table->timestamp('created_at', 3)->useCurrent();

            $table->unique(['booking_id', 'sequence']);
            $table->index(['booking_id', 'created_at']);
        });

        // Dispatch tables (P2.2) get their foreign keys now that bookings exist (ADR-86). Rows written before the
        // bookings table existed can only be test leftovers: they point at nothing and are dropped.
        DB::table('booking_dispatch_recipients')->delete();
        DB::table('provider_locations')->whereNotNull('booking_id')->update(['booking_id' => null]);

        Schema::table('booking_dispatch_recipients', function (Blueprint $table): void {
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        Schema::table('provider_locations', function (Blueprint $table): void {
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provider_locations', function (Blueprint $table): void {
            $table->dropForeign(['booking_id']);
        });

        Schema::table('booking_dispatch_recipients', function (Blueprint $table): void {
            $table->dropForeign(['booking_id']);
        });

        Schema::dropIfExists('booking_status_events');
        Schema::dropIfExists('booking_stops');
        Schema::dropIfExists('bookings');
    }
};
