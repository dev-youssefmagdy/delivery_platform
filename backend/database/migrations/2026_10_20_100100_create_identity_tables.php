<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('accessibility_needs')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamp('marketing_opt_in_at')->nullable();
            $table->timestamps();
        });

        // Stub: the onboarding columns arrive with P2.1.
        Schema::create('provider_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // One row per OTP request. The code itself is never stored, only an HMAC (ADR-61).
        Schema::create('otp_challenges', function (Blueprint $table): void {
            $table->id();
            $table->char('phone_hash', 64);
            $table->string('phone_masked', 32);
            $table->string('purpose', 32);
            $table->string('app', 16);
            $table->char('code_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts');
            $table->string('delivery_status', 16);
            $table->string('delivery_driver', 32)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('resend_available_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['phone_hash', 'purpose', 'id']);
            $table->index('created_at');
            $table->index('ip');
        });

        Schema::create('user_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('app', 16);
            $table->string('platform', 16);
            $table->string('device_id', 100);
            $table->text('push_token')->nullable();
            $table->char('push_token_hash', 64)->nullable()->index();
            $table->string('app_version', 32)->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'app', 'device_id']);
        });

        Schema::create('user_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('policy_version_id')->constrained('policy_versions')->restrictOnDelete();
            $table->string('locale', 5);
            $table->string('app', 16);
            $table->string('ip', 45)->nullable();
            $table->timestamp('accepted_at');

            $table->unique(['user_id', 'policy_version_id']);
        });

        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->string('previous_user_status', 32);
            $table->text('reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->text('processing_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->foreign('processed_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('provider_profiles');
        Schema::dropIfExists('customer_profiles');
    }
};
