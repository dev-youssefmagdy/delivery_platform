<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancellation_policies', function (Blueprint $table): void {
            // Per-minute waiting charge after the free wait (0 = waiting is only charged through a negotiated adjustment).
            $table->unsignedBigInteger('waiting_rate_minor')->default(0)->after('free_wait_seconds');
            // A customer no-show fee needs verified GPS + time evidence of the provider's arrival and of the report.
            $table->boolean('require_no_show_evidence')->default(true)->after('waiting_rate_minor');
            // A customer may report a provider no-show this long after the provider was selected, while no verified arrival exists.
            $table->unsignedInteger('provider_no_show_after_seconds')->default(600)->after('require_no_show_evidence');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            // Sum of approved booking adjustments (signed); final fare = agreed fare + this, maintained under the booking lock.
            $table->bigInteger('adjustments_total_minor')->default(0)->after('final_fare_minor');
            $table->unsignedBigInteger('cancellation_fee_entry_id')->nullable()->after('cancellation_decision');
            $table->timestamp('cancellation_fee_posted_at')->nullable()->after('cancellation_fee_entry_id');
        });

        Schema::create('booking_adjustment_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('status', 16)->default('pending');
            // customer | provider: the other party decides.
            $table->string('requester_type', 16);
            $table->unsignedBigInteger('requester_id');
            $table->char('currency', 3);
            // Booking total (agreed fare + approved adjustments) when the request was made, the total if approved, and the signed delta.
            $table->unsignedBigInteger('old_total_minor');
            $table->unsignedBigInteger('new_total_minor');
            $table->bigInteger('amount_minor');
            $table->text('reason');
            // Evidence (receipt reference, minutes, GPS) and type specific details (stop address, waiting minutes).
            $table->json('evidence')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('expires_at');
            $table->string('decided_by_type', 16)->nullable();
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['requester_type', 'requester_id']);
        });

        Schema::create('booking_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('number', 32)->unique();
            $table->string('kind', 24)->default('trip');
            $table->char('currency', 3);
            $table->unsignedBigInteger('total_minor');
            // The whole receipt as issued: fare lines, adjustments, discount, tax, fees, parties, policy. Never rebuilt.
            $table->json('snapshot');
            $table->timestamp('issued_at');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_receipts');
        Schema::dropIfExists('booking_adjustment_requests');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['adjustments_total_minor', 'cancellation_fee_entry_id', 'cancellation_fee_posted_at']);
        });

        Schema::table('cancellation_policies', function (Blueprint $table): void {
            $table->dropColumn(['waiting_rate_minor', 'require_no_show_evidence', 'provider_no_show_after_seconds']);
        });
    }
};
