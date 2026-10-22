<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A promotion (SRS §11.1). `code` is null for an automatic campaign. The financial and eligibility terms are
        // mirrored in `coupon_versions`; the columns here are the CURRENT version's values plus the live counters.
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->nullable()->unique();
            $table->string('name', 120);
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('legal_terms')->nullable();
            $table->char('currency', 3);
            $table->string('discount_type', 16);
            // Basis points for a percentage coupon, minor units for a fixed one.
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('max_discount_minor')->nullable();
            $table->unsignedBigInteger('min_fare_minor')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('global_limit')->nullable();
            $table->unsignedBigInteger('budget_minor')->nullable();
            // Committed discount money / uses, and what open reservations hold. Changed only under the coupon row lock.
            $table->unsignedBigInteger('spent_minor')->default(0);
            $table->unsignedBigInteger('reserved_minor')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('reserved_count')->default(0);
            $table->string('funding_source', 16);
            $table->json('funding_split')->nullable();
            $table->boolean('stackable')->default(false);
            $table->boolean('restore_on_refund')->default(true);
            $table->json('exclusions')->nullable();
            $table->json('segments')->nullable();
            $table->json('payment_methods')->nullable();
            $table->json('schedule')->nullable();
            $table->string('status', 16);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64);
            $table->integer('priority')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('lock_version')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['status', 'ends_at']);
            $table->index(['discount_type']);
            $table->index(['funding_source']);
        });

        // Scope pivots: service families / categories, vehicle categories, cities, zones. No row of a type = no restriction.
        Schema::create('coupon_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->string('type', 24);
            $table->unsignedBigInteger('ref_id');

            $table->unique(['coupon_id', 'type', 'ref_id']);
            $table->index(['type', 'ref_id']);
        });

        // Immutable terms of a version (Plan I4 for coupons): reservations and redemptions keep the version they started with.
        Schema::create('coupon_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('terms');
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['coupon_id', 'version']);
        });

        Schema::create('coupon_reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('coupon_version_id')->constrained('coupon_versions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('booking_id')->nullable()->unique();
            $table->string('status', 16);
            // What the reservation holds from the budget: the largest discount it can grant.
            $table->unsignedBigInteger('hold_minor');
            $table->char('currency', 3);
            $table->string('released_reason', 64)->nullable();
            $table->timestamp('reserved_at');
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'status']);
            $table->index(['user_id', 'coupon_id', 'status']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('coupon_version_id')->constrained('coupon_versions')->restrictOnDelete();
            $table->foreignId('reservation_id')->unique()->constrained('coupon_reservations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->string('status', 16);
            $table->char('currency', 3);
            $table->unsignedBigInteger('fare_minor');
            $table->unsignedBigInteger('discount_minor');
            $table->unsignedBigInteger('platform_minor')->default(0);
            $table->unsignedBigInteger('provider_minor')->default(0);
            $table->unsignedBigInteger('partner_minor')->default(0);
            $table->timestamp('redeemed_at');
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 64)->nullable();
            $table->timestamps();

            $table->index(['coupon_id', 'redeemed_at']);
            $table->index(['user_id', 'redeemed_at']);
            $table->index('booking_id');
        });

        // Every attempt (SRS §11.2 step 8): the customer's validation, the booking-time reservation, the admin's test.
        Schema::create('coupon_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('code_entered', 64)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('service_category_id')->nullable();
            $table->string('context', 16);
            $table->string('result', 24);
            $table->string('reason_code', 48);
            $table->unsignedBigInteger('discount_minor')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at', 3)->useCurrent();

            $table->index(['coupon_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['result', 'created_at']);
        });

        // Who paid how much of each redemption; the ledger entry that moved the money is linked when the trip is settled.
        Schema::create('promotion_funding_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->restrictOnDelete();
            $table->foreignId('redemption_id')->constrained('coupon_redemptions')->restrictOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->string('funder', 12);
            $table->string('kind', 12);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['coupon_id', 'funder']);
            $table->index('booking_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreign('coupon_reservation_id')->references('id')->on('coupon_reservations')->nullOnDelete();
        });

        Schema::table('coupon_reservations', function (Blueprint $table): void {
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coupon_reservations', function (Blueprint $table): void {
            $table->dropForeign(['booking_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropForeign(['coupon_reservation_id']);
        });

        Schema::dropIfExists('promotion_funding_entries');
        Schema::dropIfExists('coupon_attempts');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupon_reservations');
        Schema::dropIfExists('coupon_versions');
        Schema::dropIfExists('coupon_scopes');
        Schema::dropIfExists('coupons');
    }
};
