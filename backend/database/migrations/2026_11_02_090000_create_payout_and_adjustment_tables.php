<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where a provider receives payouts (SRS §7.6). The details (account/wallet numbers, holder name) are encrypted at rest;
        // only the type, a label and the last digits are ever shown. A method must be verified by finance before a payout can use it.
        Schema::create('provider_payout_methods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('label', 80);
            $table->text('details');
            $table->string('last4', 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_id')->nullable();
            $table->string('verification_note', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_profile_id', 'verified_at']);
        });

        Schema::create('payout_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 24)->unique();
            $table->string('status', 16);
            $table->char('currency', 3);
            $table->unsignedInteger('payout_count')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->timestamp('exported_at')->nullable();
            $table->unsignedBigInteger('exported_by_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // One withdrawal request. `reserve_entry_id` is the ledger reservation made at request time (wallet -> payout clearing):
        // it is what prevents a second payout of the same money. A request that does not end in a payment gets its reservation
        // reversed (`release_entry_id`); a paid one converts it (`paid_entry_id`).
        Schema::create('payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->foreignId('payout_method_id')->nullable()->constrained('provider_payout_methods')->nullOnDelete();
            $table->json('method_snapshot');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->unsignedBigInteger('net_minor');
            $table->char('currency', 3);
            $table->string('status', 16);
            $table->boolean('requires_dual_approval')->default(false);
            $table->foreignId('reserve_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('release_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('paid_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('first_approved_by_id')->nullable();
            $table->timestamp('first_approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('decision_note', 1000)->nullable();
            $table->string('rejection_reason', 1000)->nullable();
            $table->foreignId('payout_batch_id')->nullable()->constrained('payout_batches')->nullOnDelete();
            $table->unsignedBigInteger('processing_by_id')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->string('transfer_reference', 120)->nullable();
            $table->string('evidence_path', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason', 1000)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('idempotency_key', 64);
            $table->timestamps();

            $table->unique(['provider_profile_id', 'idempotency_key']);
            $table->index(['status', 'created_at']);
            $table->index(['provider_profile_id', 'status']);
        });

        Schema::create('payout_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payout_request_id')->constrained('payout_requests')->cascadeOnDelete();
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('note', 1000)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at', 3)->useCurrent();

            $table->index(['payout_request_id', 'id']);
        });

        // Maker-checker for manual wallet changes. The target is a customer or provider wallet; approval posts a NEW ledger entry
        // (never an edit) and the approver must be another admin than the requester.
        Schema::create('financial_adjustment_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('direction', 8);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 16);
            $table->string('reason', 1000);
            $table->string('evidence_path', 255)->nullable();
            $table->string('reference', 120)->nullable();
            $table->unsignedBigInteger('requested_by_id');
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_reason', 1000)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['ledger_account_id', 'status']);
        });

        // Settling a provider's cash commission debt. The figures are a snapshot of the period; the posting (money received by
        // the platform -> provider wallet) is made when the admin settles.
        Schema::create('cash_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->restrictOnDelete();
            $table->char('currency', 3);
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('cash_jobs')->default(0);
            $table->unsignedBigInteger('cash_collected_minor')->default(0);
            $table->unsignedBigInteger('commission_due_minor')->default(0);
            $table->unsignedBigInteger('settled_minor')->default(0);
            $table->string('status', 16);
            $table->string('method', 24)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('notes', 1000)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('settled_by_id')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_reconciliations');
        Schema::dropIfExists('financial_adjustment_requests');
        Schema::dropIfExists('payout_events');
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('payout_batches');
        Schema::dropIfExists('provider_payout_methods');
    }
};
