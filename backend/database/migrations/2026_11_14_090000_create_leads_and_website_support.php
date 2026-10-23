<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Public website (P5.5): onboarding leads created by the "become a provider" form. A lead holds contact data and interest only;
| documents are never accepted through the public site (SRS 17.3). The same visitor sending twice inside a day updates nothing and
| gets the same reference back (idempotent through `duplicate_key`).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 20)->nullable()->unique();
            $table->string('type', 24)->default('provider_onboarding');
            $table->string('status', 16)->default('new');
            $table->string('name', 120);
            $table->string('phone_e164', 20);
            $table->string('email', 191)->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('city_other', 120)->nullable();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('vehicle_note', 160)->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->default('ar');
            $table->string('source', 24)->default('website');
            $table->timestamp('consented_at');
            $table->char('ip_hash', 64)->nullable();
            // phone + type + UTC day: a second identical submission the same day returns the first lead.
            $table->string('duplicate_key', 64)->unique();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('status_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['city_id', 'status']);
            $table->index('service_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
