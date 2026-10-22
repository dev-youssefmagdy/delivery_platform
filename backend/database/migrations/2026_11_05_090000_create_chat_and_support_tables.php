<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Booking-scoped chat between the customer and the selected provider (P3.7, SRS §14.2).
        Schema::create('booking_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // customer | provider; `sender_user_id` is the account behind it.
            $table->string('sender_type', 16);
            $table->foreignId('sender_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime', 64)->nullable();
            $table->unsignedInteger('attachment_size')->nullable();
            $table->string('attachment_hash', 64)->nullable();
            $table->string('attachment_scan_status', 16)->nullable();
            // The other side opened the conversation after this message (read receipt).
            $table->timestamp('read_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'id']);
            $table->index(['booking_id', 'sender_type', 'read_at']);
        });

        Schema::create('support_macros', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title_ar', 120);
            $table->string('title_en', 120)->nullable();
            $table->text('body_ar');
            $table->text('body_en')->nullable();
            $table->string('category', 32)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['active', 'category']);
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number', 20)->nullable()->unique();
            // The mobile user and the app they wrote from (a person with both apps has separate tickets per app).
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('app', 16);
            $table->string('category', 32);
            $table->string('priority', 16);
            $table->string('status', 16)->default('open');
            $table->string('source', 16)->default('app');
            $table->string('subject', 160);
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('payout_request_id')->nullable()->constrained('payout_requests')->nullOnDelete();
            $table->string('group', 16)->default('support');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('sla_breached_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_staff_reply_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_reason')->nullable();
            // The cancellation-fee decision (or other context) an appeal was opened with, copied at creation.
            $table->json('context')->nullable();
            // The `booking_reports` row this ticket was migrated or created from (P2.5 reports).
            $table->unsignedBigInteger('source_report_id')->nullable()->unique();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'app', 'status']);
            $table->index(['status', 'group', 'assigned_admin_id']);
            $table->index(['status', 'sla_due_at']);
            $table->index('booking_id');
            $table->index('category');
        });

        Schema::create('ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('visibility', 16)->default('public');
            // customer | provider | admin | system
            $table->string('author_type', 16);
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('macro_id')->nullable()->constrained('support_macros')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['ticket_id', 'id']);
        });

        Schema::create('ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 120)->nullable();
            $table->string('mime', 64);
            $table->unsignedInteger('size_bytes');
            $table->string('file_hash', 64);
            $table->string('scan_status', 16);
            $table->timestamps();
        });

        Schema::create('ticket_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ticket_id', 'id']);
        });

        Schema::table('booking_reports', function (Blueprint $table): void {
            $table->unsignedBigInteger('ticket_id')->nullable()->after('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_reports', function (Blueprint $table): void {
            $table->dropColumn('ticket_id');
        });
        Schema::dropIfExists('ticket_events');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_macros');
        Schema::dropIfExists('booking_messages');
    }
};
