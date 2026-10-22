<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per event, channel and locale; the content uses {variable} placeholders (P3.6).
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 64);
            $table->string('channel', 16);
            $table->char('locale', 2);
            // Push title / email subject; unused for SMS.
            $table->string('title', 160)->nullable();
            $table->text('body');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_key', 'channel', 'locale']);
        });

        Schema::create('push_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('audience', 16);
            $table->string('title_ar', 160);
            $table->text('body_ar');
            $table->string('title_en', 160)->nullable();
            $table->text('body_en')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('cancelled_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });

        // The in-app inbox: one row per notification, for a mobile user (`user`) or an admin (`admin`).
        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('recipient_type', 16);
            $table->unsignedBigInteger('recipient_id');
            // The app whose inbox shows it (customer | provider); null for admins.
            $table->string('app', 16)->nullable();
            $table->string('event_key', 64);
            $table->string('category', 24);
            $table->string('title', 160);
            $table->text('body');
            $table->char('locale', 2);
            $table->string('link_type', 24)->nullable();
            $table->string('link_id', 64)->nullable();
            $table->json('data')->nullable();
            // Same key for the same person = same notification (no second row, no second push).
            $table->string('dedupe_key', 120)->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained('push_campaigns')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');

            $table->unique(['recipient_type', 'recipient_id', 'dedupe_key'], 'app_notifications_dedupe_unique');
            $table->index(['recipient_type', 'recipient_id', 'app', 'created_at'], 'app_notifications_inbox_index');
            $table->index(['recipient_type', 'recipient_id', 'read_at'], 'app_notifications_unread_index');
            $table->index(['event_key', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('notification_id')->constrained('app_notifications')->cascadeOnDelete();
            $table->string('recipient_type', 16);
            $table->unsignedBigInteger('recipient_id');
            $table->string('event_key', 64);
            $table->string('channel', 16);
            $table->string('status', 16)->default('queued');
            $table->string('provider', 24)->nullable();
            $table->string('provider_message_id', 190)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 255)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            // Template that produced the content, so a change later never rewrites history.
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->unsignedInteger('template_version')->nullable();
            $table->string('title', 160)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->unique(['notification_id', 'channel']);
            $table->index(['channel', 'status', 'created_at']);
            $table->index(['event_key', 'created_at']);
            $table->index(['status', 'next_attempt_at']);
            $table->index(['recipient_type', 'recipient_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 24);
            $table->string('channel', 16);
            $table->boolean('enabled');
            $table->timestamps();

            $table->unique(['user_id', 'category', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('push_campaigns');
        Schema::dropIfExists('notification_templates');
    }
};
