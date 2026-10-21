<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only history (sampled). Retention: see DECISIONS ADR-83 (monthly partitions, 30 days online / 90 days
        // for rows tied to a booking, pruned by `locations:prune`).
        Schema::create('provider_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->geometry('point', 'point', 4326);
            $table->float('accuracy_m')->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->float('speed_mps')->nullable();
            $table->string('source', 16);
            $table->string('anomaly', 32)->nullable();
            // The booking being served when the sample was taken; the foreign key is added with the bookings table (P2.3).
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->timestamp('recorded_at', 3);
            $table->timestamp('created_at')->useCurrent();

            $table->spatialIndex('point');
            $table->index(['provider_profile_id', 'recorded_at']);
            $table->index('recorded_at');
            $table->index('booking_id');
        });

        Schema::create('provider_last_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_profile_id')->unique()->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->geometry('point', 'point', 4326);
            $table->float('accuracy_m')->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->float('speed_mps')->nullable();
            $table->string('source', 16);
            $table->timestamp('recorded_at', 3);
            $table->timestamp('received_at', 3);
            $table->timestamps();

            $table->spatialIndex('point');
            $table->index('recorded_at');
            $table->index('city_id');
        });

        // Which providers were notified of which request, in which wave (SRS §10.2). `booking_id` gets its foreign key
        // with the bookings table (P2.3); until then it is a plain id.
        Schema::create('booking_dispatch_recipients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->unsignedSmallInteger('wave');
            $table->unsignedInteger('distance_m');
            $table->timestamp('notified_at');
            $table->timestamp('seen_at')->nullable();
            $table->string('response', 16)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'provider_profile_id'], 'dispatch_recipients_booking_provider_unique');
            $table->index(['provider_profile_id', 'notified_at'], 'dispatch_recipients_provider_notified_index');
            $table->index(['booking_id', 'wave'], 'dispatch_recipients_booking_wave_index');
        });

        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->string('last_offline_reason', 32)->nullable()->after('online_since');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->dropColumn('last_offline_reason');
        });
        Schema::dropIfExists('booking_dispatch_recipients');
        Schema::dropIfExists('provider_last_locations');
        Schema::dropIfExists('provider_locations');
    }
};
