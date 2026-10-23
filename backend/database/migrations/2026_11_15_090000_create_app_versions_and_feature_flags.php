<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| System operations (P5.6): per app + platform minimum version, force-update switch and store link (served by /config/app),
| and feature flags with targeting by app, city and percentage rollout.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('app', 16);
            $table->string('platform', 16);
            $table->string('min_version', 32)->default('1.0.0');
            $table->string('latest_version', 32)->nullable();
            $table->boolean('force_update')->default(false);
            $table->string('store_url', 500)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['app', 'platform']);
        });

        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->boolean('enabled')->default(false);
            $table->json('apps')->nullable();
            $table->json('city_ids')->nullable();
            $table->unsignedTinyInteger('rollout_percent')->default(100);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('app_versions');
    }
};
