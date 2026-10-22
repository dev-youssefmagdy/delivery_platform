<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sender / recipient of a parcel (SRS §5.1-C). Name and phone are encrypted at rest and removed on account deletion.
        Schema::create('booking_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('name_encrypted')->nullable();
            $table->text('phone_encrypted')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'role']);
        });

        // One recipient code per booking. Only an HMAC is stored; the plain code only ever goes to the SMS gateway.
        Schema::create('booking_recipient_otps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts');
            $table->unsignedSmallInteger('resend_count')->default(0);
            $table->string('delivery_status', 16)->default('pending');
            $table->string('delivery_driver', 24)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('resend_available_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('outcome', 32);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('evidence_proof_id')->nullable();
            // pending until the customer, the provider or operations decide: retry, return_to_sender, operations.
            $table->string('resolution', 24)->nullable();
            $table->string('resolved_by_type', 16)->nullable();
            $table->unsignedBigInteger('resolved_by_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'attempt_number']);
        });

        Schema::table('booking_proofs', function (Blueprint $table): void {
            // pickup (default for rows that existed before: the PIN), delivery or return.
            $table->string('stage', 12)->default('pickup')->after('proof_type');
            $table->string('mime', 64)->nullable()->after('sha256');
            $table->unsignedInteger('size_bytes')->nullable()->after('mime');
            $table->string('scan_status', 16)->nullable()->after('size_bytes');

            $table->index(['booking_id', 'stage', 'proof_type']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            // Restricted goods acknowledgement (policy version) required at creation of a parcel booking.
            $table->unsignedBigInteger('restricted_goods_policy_version_id')->nullable()->after('payment_method');
            $table->timestamp('restricted_goods_acknowledged_at')->nullable()->after('restricted_goods_policy_version_id');
            // Where the parcel is in the custody chain (picked_up, in_transit, arrived_dropoff, attempt_failed, returning, delivered, returned).
            $table->string('custody_status', 24)->nullable()->after('status');
            $table->unsignedSmallInteger('delivery_attempts_count')->default(0)->after('custody_status');
            // Category-specific searchable columns copied from the validated service details (admin filters).
            $table->string('parcel_size', 16)->nullable();
            $table->string('parcel_category', 24)->nullable();
            $table->boolean('is_fragile')->nullable();
            $table->unsignedBigInteger('declared_value_minor')->nullable();

            $table->index('custody_status');
            $table->index('parcel_size');
            $table->index('is_fragile');
            $table->index('declared_value_minor');
            $table->index('cargo_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['custody_status']);
            $table->dropIndex(['parcel_size']);
            $table->dropIndex(['is_fragile']);
            $table->dropIndex(['declared_value_minor']);
            $table->dropIndex(['cargo_weight_kg']);
            $table->dropColumn([
                'restricted_goods_policy_version_id', 'restricted_goods_acknowledged_at', 'custody_status', 'delivery_attempts_count',
                'parcel_size', 'parcel_category', 'is_fragile', 'declared_value_minor',
            ]);
        });

        Schema::table('booking_proofs', function (Blueprint $table): void {
            $table->dropIndex(['booking_id', 'stage', 'proof_type']);
            $table->dropColumn(['stage', 'mime', 'size_bytes', 'scan_status']);
        });

        Schema::dropIfExists('delivery_attempts');
        Schema::dropIfExists('booking_recipient_otps');
        Schema::dropIfExists('booking_contacts');
    }
};
