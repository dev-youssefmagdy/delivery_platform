<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounts of the double-entry ledger (SRS §12.3). `balance_minor` is a cache of the lines, in the account's
        // natural sign (normal-side lines minus opposite-side lines); it is updated only by LedgerService under a row
        // lock, and `ledger:reconcile` proves it equals the sum of the lines (Plan I6).
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 32);
            // Morph alias of the owner (user, provider_profile); both null for platform accounts.
            $table->string('owner_type', 32)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            // 'platform' or '<owner_type>:<owner_id>': makes (type, scope, currency) unique even for NULL owners.
            $table->string('scope_key', 80);
            $table->char('currency', 3);
            $table->bigInteger('balance_minor')->default(0);
            $table->boolean('allow_negative')->default(false);
            // How far below zero an account that allows negatives may go; null = no limit.
            $table->unsignedBigInteger('credit_limit_minor')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['type', 'scope_key', 'currency'], 'ledger_accounts_identity_unique');
            $table->index(['owner_type', 'owner_id']);
            $table->index(['type', 'currency']);
        });

        // Immutable journal: rows are only inserted. The repository has no update/delete method, the models refuse
        // them. Database triggers are optional hardening (they need the TRIGGER/SUPER privilege, see docs/SETUP.md).
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 32);
            $table->string('reference_type', 32)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description_key', 120);
            $table->json('meta')->nullable();
            $table->timestamp('posted_at', 3);
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('idempotency_key', 120)->unique();
            // Hash of the lines, so a replay with the same key but different content is refused.
            $table->char('fingerprint', 64);
            $table->timestamp('created_at', 3)->useCurrent();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['type', 'posted_at']);
            $table->index('posted_at');
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('side', 6);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            // The account's cached balance right after this line (statements without re-summing).
            $table->bigInteger('balance_after_minor');
            $table->timestamp('created_at', 3)->useCurrent();

            $table->index('journal_entry_id');
            $table->index(['ledger_account_id', 'id']);
        });

        Schema::create('ledger_reconciliation_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 16);
            $table->unsignedInteger('accounts_checked')->default(0);
            $table->unsignedInteger('entries_checked')->default(0);
            // [{kind, account_uuid?|entry_uuid?, expected, actual, detail}]; reported, never auto-fixed.
            $table->json('findings')->nullable();
            $table->string('triggered_by_type', 16);
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->timestamp('ran_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('ran_at');
            $table->index('status');
        });

        // Commission configuration (SRS §12.4): the rule holds scope, period and state; every change of the numbers
        // is a new immutable version, and bookings keep a snapshot of the version that applied to them (Plan I4).
        Schema::create('commission_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->restrictOnDelete();
            $table->foreignId('service_zone_id')->nullable()->constrained('service_zones')->restrictOnDelete();
            // Reserved scopes: a provider tier code and a campaign code. A rule with one only matches a context that carries the same code.
            $table->string('provider_tier', 40)->nullable();
            $table->string('campaign_code', 40)->nullable();
            $table->char('currency', 3);
            // Higher wins before specificity is compared.
            $table->smallInteger('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['active', 'effective_from'], 'commission_rules_lookup_index');
            $table->index(['service_category_id', 'service_zone_id']);
        });

        Schema::create('commission_rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('type', 16);
            $table->unsignedInteger('percent_bp')->default(0);
            $table->unsignedBigInteger('fixed_minor')->default(0);
            $table->unsignedBigInteger('min_minor')->nullable();
            $table->unsignedBigInteger('max_minor')->nullable();
            // ADR-18: tips are commission-free unless a rule version says otherwise.
            $table->boolean('commission_on_tips')->default(false);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['commission_rule_id', 'version']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            // The rule version applied at provider selection (the full values are in commission_snapshot).
            $table->unsignedBigInteger('commission_rule_id')->nullable()->after('commission_snapshot');
            $table->unsignedBigInteger('commission_rule_version_id')->nullable()->after('commission_rule_id');
            // Every amount of the finished trip, produced once at completion by PriceBreakdownCalculator (Plan I4, I12).
            $table->json('price_breakdown')->nullable()->after('commission_rule_version_id');
            $table->timestamp('settled_at')->nullable()->after('price_breakdown');

            $table->index('commission_rule_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['commission_rule_id']);
            $table->dropColumn(['commission_rule_id', 'commission_rule_version_id', 'price_breakdown', 'settled_at']);
        });

        Schema::dropIfExists('commission_rule_versions');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('ledger_reconciliation_runs');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
