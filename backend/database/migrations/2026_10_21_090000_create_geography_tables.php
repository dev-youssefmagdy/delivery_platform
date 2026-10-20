<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->json('name');
            $table->string('timezone', 64);
            $table->geometry('center', 'point', 4326);
            $table->boolean('active')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'active']);
        });

        Schema::create('service_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->json('name');
            $table->geometry('polygon', 'polygon', 4326);
            $table->string('status', 16)->default('draft');
            $table->json('operating_hours')->nullable();
            $table->boolean('restricted_pickup')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->spatialIndex('polygon');
            $table->index(['city_id', 'status']);
        });

        Schema::create('zone_service_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_zone_id')->constrained('service_zones')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->json('overrides')->nullable();
            $table->timestamps();

            $table->unique(['service_zone_id', 'service_category_id'], 'zone_category_unique');
        });

        Schema::create('saved_places', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('type', 16);
            $table->geometry('point', 'point', 4326);
            $table->json('address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_places');
        Schema::dropIfExists('zone_service_category');
        Schema::dropIfExists('service_zones');
        Schema::dropIfExists('cities');
    }
};
