<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_offers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('kind', 24);
            $table->string('status', 40);
            // The newest version; the amount lives only in booking_offer_versions (Plan I3). Foreign key added below.
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedSmallInteger('revision_count')->default(0);
            $table->string('idempotency_key', 64)->nullable();
            // 1 while the offer is active, NULL once it is closed: the unique index below lets a provider hold at most
            // one active offer per booking (NULLs never collide).
            $table->unsignedTinyInteger('active_marker')->nullable();
            // 1 only on the accepted offer: the database itself refuses a second winner for a booking (Plan I1).
            $table->unsignedTinyInteger('winner_marker')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason', 40)->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'provider_profile_id', 'active_marker'], 'booking_offers_one_active_per_provider');
            $table->unique(['booking_id', 'winner_marker'], 'booking_offers_one_winner_per_booking');
            $table->index(['booking_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['provider_profile_id', 'status']);
        });

        // Append-only: an offer never changes its amount, a revision is a new row (Plan I3, SRS §7.4).
        Schema::create('booking_offer_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->constrained('booking_offers')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('note', 500)->nullable();
            $table->json('scope')->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamp('created_at', 3)->useCurrent();

            $table->unique(['offer_id', 'version']);
            $table->unique(['offer_id', 'idempotency_key']);
        });

        Schema::table('booking_offers', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('booking_offer_versions')->nullOnDelete();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            // The fare and scope the customer chose, copied at selection (Plan I3/I4); nothing reads the offer for money afterwards.
            $table->unsignedBigInteger('agreed_fare_minor')->nullable()->after('selected_offer_id');
            $table->unsignedBigInteger('agreed_offer_version_id')->nullable()->after('agreed_fare_minor');
            $table->json('agreed_scope')->nullable()->after('agreed_offer_version_id');
            $table->timestamp('provider_selected_at')->nullable()->after('agreed_scope');
            $table->foreign('selected_offer_id')->references('id')->on('booking_offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['selected_offer_id']);
            $table->dropColumn(['agreed_fare_minor', 'agreed_offer_version_id', 'agreed_scope', 'provider_selected_at']);
        });

        Schema::table('booking_offers', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('booking_offer_versions');
        Schema::dropIfExists('booking_offers');
    }
};
