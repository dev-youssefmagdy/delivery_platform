<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People a user chose to tell when something goes wrong or when a trip is shared (SRS §13.1). The phone is encrypted at rest;
        // `phone_hash` (keyed hash) only stops the same number being added twice.
        Schema::create('trusted_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->text('phone_encrypted');
            $table->string('phone_hash', 64);
            $table->string('relationship', 24)->nullable();
            // The user confirmed the person agreed to receive messages from the platform.
            $table->timestamp('consent_confirmed_at');
            // What the person may receive: an alert when the user raises an SOS, the live trip link, the GPS point in that alert.
            $table->boolean('notify_on_sos')->default(true);
            $table->boolean('share_trip')->default(true);
            $table->boolean('share_location_on_sos')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'phone_hash']);
        });

        Schema::create('safety_incidents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number', 20)->nullable()->unique();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->restrictOnDelete();
            // customer | provider: the app the report came from.
            $table->string('reporter_type', 16);
            // sos | report
            $table->string('trigger', 16);
            $table->string('category', 32);
            $table->string('severity', 16);
            $table->string('priority', 16);
            $table->string('status', 16)->default('new');
            // Everything below that can identify a person or a place is encrypted at rest.
            $table->text('narrative')->nullable();
            $table->text('callback_phone_encrypted')->nullable();
            $table->text('location_snapshot')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('sla_breached_at')->nullable();
            $table->string('escalation_level', 24)->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            // A hold keeps the case and its evidence through retention clean-up and account deletion; set and released with a reason.
            $table->boolean('legal_hold')->default(false);
            $table->timestamp('legal_hold_at')->nullable();
            $table->foreignId('legal_hold_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('legal_hold_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['status', 'priority', 'sla_due_at']);
            $table->index(['booking_id']);
            $table->index(['reporter_user_id', 'created_at']);
            $table->index(['assigned_admin_id', 'status']);
        });

        Schema::create('safety_incident_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_id')->constrained('safety_incidents')->cascadeOnDelete();
            $table->string('type', 32);
            // customer | provider | admin | system
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            // Staff note or a message to / from the user (encrypted).
            $table->text('body')->nullable();
            // public: the person may read it; internal: staff only.
            $table->boolean('is_public')->default(false);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['incident_id', 'id']);
        });

        Schema::create('safety_incident_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('incident_id')->constrained('safety_incidents')->cascadeOnDelete();
            $table->string('uploader_type', 16);
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->string('path')->nullable();
            $table->string('mime', 64);
            $table->unsignedInteger('size_bytes');
            $table->string('file_hash', 64);
            $table->string('scan_status', 16);
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            $table->index('incident_id');
        });

        // What the platform actually did towards a trusted contact (SRS §16.10 "trusted-contact action record"): never the phone in clear.
        Schema::create('trusted_contact_actions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trusted_contact_id')->nullable()->constrained('trusted_contacts')->nullOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('safety_incidents')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            // sos_alert | trip_share
            $table->string('action', 16);
            $table->string('channel', 16)->default('sms');
            // sent | failed | skipped
            $table->string('status', 16);
            $table->string('reason', 48)->nullable();
            $table->string('contact_name', 80)->nullable();
            $table->string('contact_phone_masked', 32)->nullable();
            $table->boolean('included_location')->default(false);
            $table->boolean('included_trip_link')->default(false);
            $table->string('gateway', 24)->nullable();
            $table->timestamp('trip_link_expires_at')->nullable();
            $table->timestamps();

            $table->index(['incident_id']);
            $table->index(['booking_id', 'action']);
            $table->index(['user_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_contact_actions');
        Schema::dropIfExists('safety_incident_attachments');
        Schema::dropIfExists('safety_incident_events');
        Schema::dropIfExists('safety_incidents');
        Schema::dropIfExists('trusted_contacts');
    }
};
