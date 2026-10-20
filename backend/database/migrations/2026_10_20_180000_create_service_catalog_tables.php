<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_families', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('kind', 32);
            $table->string('icon', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });

        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('map_marker', 64)->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->nullable();
            $table->unsignedSmallInteger('luggage_capacity')->nullable();
            $table->decimal('max_payload_kg', 10, 2)->nullable();
            $table->decimal('max_volume_m3', 10, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });

        Schema::create('service_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_family_id')->constrained('service_families')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('map_marker', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(false);
            $table->json('allowed_booking_types');

            $table->boolean('negotiation_enabled')->default(true);
            $table->boolean('reference_estimate_enabled')->default(true);
            $table->unsignedBigInteger('min_customer_fare_minor')->nullable();
            $table->unsignedBigInteger('max_customer_fare_minor')->nullable();
            $table->unsignedSmallInteger('counter_offer_min_pct')->nullable();
            $table->unsignedSmallInteger('counter_offer_max_pct')->nullable();

            $table->unsignedInteger('offer_ttl_seconds')->nullable();
            $table->unsignedInteger('search_ttl_seconds')->nullable();
            $table->json('dispatch_radius_steps_km')->nullable();
            $table->unsignedSmallInteger('dispatch_batch_size')->nullable();

            $table->unsignedSmallInteger('max_passengers')->nullable();
            $table->unsignedSmallInteger('max_luggage')->nullable();
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_volume_m3', 10, 2)->nullable();
            $table->boolean('supports_stops')->default(false);
            $table->unsignedSmallInteger('max_stops')->default(0);

            $table->boolean('pin_required')->nullable();
            $table->boolean('pickup_proof_required')->default(false);
            $table->json('delivery_proof_types');
            $table->boolean('recipient_otp_required')->default(false);

            $table->boolean('tipping_enabled')->default(true);
            $table->boolean('coupon_eligible')->default(true);
            $table->boolean('cash_allowed')->default(true);
            $table->json('payment_methods');
            $table->json('service_hours')->nullable();

            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['service_family_id', 'active', 'sort_order']);
        });

        Schema::create('category_vehicle_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->restrictOnDelete();

            $table->unique(['service_category_id', 'vehicle_category_id'], 'category_vehicle_unique');
        });

        Schema::create('service_form_schemas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->unsignedInteger('version')->nullable();
            $table->json('fields');
            $table->string('status', 16);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['service_category_id', 'version']);
            $table->index(['service_category_id', 'status']);
        });

        Schema::create('service_category_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('service_category_id')->constrained('service_categories')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('form_schema_id')->constrained('service_form_schemas')->restrictOnDelete();
            $table->json('snapshot');
            $table->string('reason', 500);
            $table->foreignId('published_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['service_category_id', 'version']);
        });

        Schema::table('service_categories', function (Blueprint $table): void {
            $table->foreign('published_version_id')->references('id')->on('service_category_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table): void {
            $table->dropForeign(['published_version_id']);
        });
        Schema::dropIfExists('service_category_versions');
        Schema::dropIfExists('service_form_schemas');
        Schema::dropIfExists('category_vehicle_category');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('vehicle_categories');
        Schema::dropIfExists('service_families');
    }
};
