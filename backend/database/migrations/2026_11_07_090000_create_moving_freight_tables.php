<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P4.2 - furniture moving and freight: the structured load (booking_items), photos / videos / documents on the private
 * disk (booking_attachments), the customer's requested scope, volume and pickup window as searchable columns and the
 * encrypted contact of an intermediate stop. Offer versions already carry the scope (P2.4); nothing here edits them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // checklist (an option of the schema), free (typed text) or cargo (a freight load line).
            $table->string('kind', 12);
            $table->string('code', 48)->nullable();
            $table->string('label', 255)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('volume_m3', 10, 3)->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['booking_id', 'position']);
        });

        Schema::create('booking_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            // Null while the file was uploaded but not yet referenced by a booking request.
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            // The service form field that references it (photos, documents ...).
            $table->string('field', 48)->nullable();
            $table->string('kind', 12);
            $table->string('disk', 24);
            $table->text('storage_path')->nullable();
            $table->string('mime', 64);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('scan_status', 16)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'kind']);
            $table->index(['customer_id', 'booking_id']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            // The scope the customer asked for, built from the service form; "accept the customer's fare" offers carry it.
            $table->json('requested_scope')->nullable()->after('agreed_scope');
            $table->decimal('cargo_volume_m3', 10, 3)->nullable()->after('cargo_weight_kg');
            $table->timestamp('pickup_window_start')->nullable()->after('scheduled_at');
            $table->timestamp('pickup_window_end')->nullable()->after('pickup_window_start');
        });

        Schema::table('booking_stops', function (Blueprint $table): void {
            // {name, phone, note} encrypted as one value; shown only to the selected provider.
            $table->text('contact_encrypted')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('booking_stops', function (Blueprint $table): void {
            $table->dropColumn('contact_encrypted');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['requested_scope', 'cargo_volume_m3', 'pickup_window_start', 'pickup_window_end']);
        });

        Schema::dropIfExists('booking_attachments');
        Schema::dropIfExists('booking_items');
    }
};
