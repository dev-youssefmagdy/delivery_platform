<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('heading_at')->nullable()->after('provider_selected_at');
            $table->timestamp('arrived_at')->nullable()->after('heading_at');
            // GPS evidence of the arrival (point, accuracy, distance to pickup, captured time); never used to penalise alone.
            $table->json('arrival_evidence')->nullable()->after('arrived_at');
            $table->timestamp('free_wait_ends_at')->nullable()->after('arrival_evidence');
            $table->timestamp('started_at')->nullable()->after('free_wait_ends_at');
            $table->unsignedSmallInteger('pin_attempts')->default(0)->after('pin_encrypted');
            $table->timestamp('pin_verified_at')->nullable()->after('pin_attempts');
            $table->timestamp('pin_locked_at')->nullable()->after('pin_verified_at');
            $table->timestamp('support_flagged_at')->nullable()->after('pin_locked_at');
            $table->string('support_flag_reason', 64)->nullable()->after('support_flagged_at');
            // Totals are computed on the server: the agreed fare plus approved adjustments (none exist before P4).
            $table->unsignedBigInteger('final_fare_minor')->nullable()->after('agreed_scope');
            $table->string('cancellation_reason_code', 40)->nullable()->after('cancellation_reason');
            $table->unsignedBigInteger('cancellation_fee_minor')->nullable()->after('cancellation_reason_code');
            // The fee decision as it was shown and applied (policy id, stage, rule, amount); posting to the ledger is P3.5.
            $table->json('cancellation_decision')->nullable()->after('cancellation_fee_minor');
        });

        Schema::create('cancellation_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->restrictOnDelete();
            $table->foreignId('service_zone_id')->nullable()->constrained('service_zones')->restrictOnDelete();
            // Customer cancellations within this many seconds after the provider was selected are free.
            $table->unsignedInteger('free_grace_seconds')->default(120);
            // Free waiting time after arrival before a no-show is accepted as the reason.
            $table->unsignedInteger('free_wait_seconds')->default(300);
            // [{actor, stage, fee_type: none|fixed|percent, value, cap_minor?, recipient: provider|platform}]
            $table->json('rules');
            $table->json('customer_reasons');
            $table->json('provider_reasons');
            $table->boolean('active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['service_category_id', 'service_zone_id', 'active', 'effective_from'], 'cancellation_policies_scope_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('cancellation_policy_id')->nullable()->after('fare_policy_id');
        });

        Schema::create('booking_proofs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('proof_type', 24);
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            // Private storage reference and content hash (files arrive with P4; a PIN proof has neither).
            $table->string('storage_disk', 32)->nullable();
            $table->string('storage_path', 255)->nullable();
            $table->char('sha256', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['booking_id', 'proof_type']);
        });

        Schema::create('booking_ratings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // customer rates provider, provider rates customer: one rating per booking and rater side.
            $table->string('rater_type', 16);
            $table->unsignedBigInteger('rater_id');
            $table->string('ratee_type', 16);
            $table->unsignedBigInteger('ratee_id');
            $table->unsignedTinyInteger('stars');
            $table->json('tags')->nullable();
            $table->text('comment')->nullable();
            $table->string('moderation_status', 24)->default('visible');
            $table->timestamps();

            $table->unique(['booking_id', 'rater_type'], 'booking_ratings_one_per_side');
            $table->index(['ratee_type', 'ratee_id', 'moderation_status'], 'booking_ratings_ratee_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
        });

        Schema::create('booking_share_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // Only the hash of the token is stored; the token itself is shown once to the customer.
            $table->char('token_hash', 64)->unique();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['booking_id', 'expires_at']);
        });

        Schema::create('booking_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('reporter_type', 16);
            $table->unsignedBigInteger('reporter_id');
            $table->string('category', 40);
            $table->text('description');
            // P3.7 migrates open rows into support tickets.
            $table->string('status', 24)->default('open');
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_reports');
        Schema::dropIfExists('booking_share_links');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });

        Schema::dropIfExists('booking_ratings');
        Schema::dropIfExists('booking_proofs');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('cancellation_policy_id');
        });

        Schema::dropIfExists('cancellation_policies');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'heading_at', 'arrived_at', 'arrival_evidence', 'free_wait_ends_at', 'started_at', 'pin_attempts', 'pin_verified_at',
                'pin_locked_at', 'support_flagged_at', 'support_flag_reason', 'final_fare_minor', 'cancellation_reason_code',
                'cancellation_fee_minor', 'cancellation_decision',
            ]);
        });
    }
};
