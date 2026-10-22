<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saved online payment methods (SRS §12.1). Only a gateway token reference (encrypted) and display data are kept:
        // never a card number. A row starts `pending` when a setup intent is created and becomes `active` when the
        // gateway's verified webhook reports the saved method. Removing a method soft-deletes it so past payments keep their link.
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('gateway', 24);
            $table->string('status', 12);
            $table->string('setup_reference', 120)->nullable();
            $table->text('gateway_token')->nullable();
            $table->string('brand', 24)->nullable();
            $table->char('last4', 4)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->unique(['gateway', 'setup_reference']);
        });

        // One charge: the trip (kind=trip) or a tip. `status` is the PaymentStatus axis; `paid` is written only from a
        // verified webhook or gateway query (Plan I8) and `cash_collected` only from the provider's confirmation.
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->string('kind', 8);
            $table->string('method_type', 24);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('gateway', 24)->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 32);
            $table->string('gateway_reference', 120)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            // When the gateway said the last applied thing: older events never overwrite newer state (out-of-order webhooks).
            $table->timestamp('last_event_at', 3)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settlement_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_reference']);
            $table->index(['booking_id', 'kind', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->string('source', 16);
            $table->string('actor_type', 16)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('gateway_event_id', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at', 3)->useCurrent();

            $table->index(['payment_id', 'id']);
        });

        // Every callback the gateways send, valid or not. (gateway, event_id) is unique: the second delivery of an event
        // is recognised and not applied again (Plan I8, SRS §12.2). Invalid signatures are kept with a synthetic id.
        Schema::create('gateway_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('gateway', 24);
            $table->string('event_id', 160);
            $table->string('kind', 32)->nullable();
            $table->boolean('signature_valid');
            $table->char('payload_hash', 64);
            $table->json('payload')->nullable();
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('occurred_at', 3)->nullable();
            $table->timestamp('received_at', 3);
            $table->timestamp('processed_at', 3)->nullable();
            $table->string('outcome', 24);
            $table->string('error', 255)->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->unsignedBigInteger('reprocessed_by_id')->nullable();
            $table->string('reprocess_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
            $table->index(['outcome', 'received_at']);
            $table->index('received_at');
        });

        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 24);
            $table->string('reason', 1000);
            $table->boolean('requires_dual_approval')->default(false);
            $table->unsignedBigInteger('requested_by_id');
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_reason', 1000)->nullable();
            $table->string('gateway_reference', 120)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->unique(['payment_id', 'gateway_reference']);
        });

        Schema::create('payment_reconciliation_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 16);
            $table->timestamp('window_from');
            $table->timestamp('window_to');
            $table->unsignedInteger('payments_checked')->default(0);
            $table->unsignedInteger('transactions_checked')->default(0);
            $table->unsignedInteger('timed_out')->default(0);
            $table->json('findings')->nullable();
            $table->string('triggered_by_type', 16);
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->timestamp('ran_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('ran_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_runs');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('gateway_webhook_events');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};
