<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Growth (P5.7, SRS 11.3 / 7.6): referral campaigns, codes, relationships and rewards; loyalty levels and members; provider incentive
| campaigns and per-provider progress. Money is integer minor units; every payout of a reward or bonus is a ledger entry referenced
| by `journal_entry_id` and keyed by `idempotency_key`. `growth_processed_bookings` makes the completion hook idempotent per booking.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('name_en', 120)->nullable();
            $table->string('audience', 12);
            $table->string('status', 12)->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->char('currency', 3);
            $table->unsignedBigInteger('referrer_reward_minor')->default(0);
            $table->unsignedBigInteger('referee_reward_minor')->default(0);
            // Milestone: the referee must complete this many qualifying jobs.
            $table->unsignedSmallInteger('milestone_jobs')->default(1);
            $table->unsignedBigInteger('min_order_minor')->nullable();
            $table->json('service_category_ids')->nullable();
            $table->json('city_ids')->nullable();
            $table->unsignedSmallInteger('credit_valid_days')->default(30);
            $table->unsignedInteger('per_referrer_cap')->default(20);
            $table->unsignedBigInteger('budget_minor')->default(0);
            $table->unsignedBigInteger('spent_minor')->default(0);
            $table->text('status_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'status']);
        });

        Schema::create('referral_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('audience', 12);
            $table->string('code', 12)->unique();
            $table->timestamps();

            $table->unique(['user_id', 'audience']);
        });

        Schema::create('referral_relationships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained('referral_campaigns')->restrictOnDelete();
            $table->foreignId('referral_code_id')->constrained('referral_codes')->restrictOnDelete();
            $table->string('audience', 12);
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->unsignedSmallInteger('jobs_completed')->default(0);
            // Weak signals only (list of {flag, evidence}); they hold the reward for a person to look at, they never punish.
            $table->json('fraud_flags')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            // A person can be referred once per audience.
            $table->unique(['referee_user_id', 'audience']);
            $table->index(['campaign_id', 'status']);
            $table->index(['referrer_user_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('referral_rewards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('relationship_id')->constrained('referral_relationships')->restrictOnDelete();
            $table->foreignId('campaign_id')->constrained('referral_campaigns')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient', 10);
            $table->string('wallet_type', 16);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('expired_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 10)->default('granted');
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('expiry_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamps();

            $table->unique(['relationship_id', 'recipient']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('loyalty_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('name_en', 80)->nullable();
            $table->unsignedInteger('min_completed_jobs')->unique();
            $table->unsignedBigInteger('reward_minor')->default(0);
            $table->char('currency', 3);
            $table->unsignedSmallInteger('credit_valid_days')->default(30);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('status_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->foreignId('level_id')->nullable()->constrained('loyalty_levels')->nullOnDelete();
            $table->timestamp('level_reached_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('loyalty_levels')->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->unsignedBigInteger('expired_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 10)->default('granted');
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('expiry_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamps();

            $table->unique(['user_id', 'level_id']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('incentive_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 120);
            $table->string('name_en', 120)->nullable();
            $table->string('status', 12)->default('draft');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->char('currency', 3);
            // Quest: complete `target_jobs` qualifying jobs inside the window to earn `bonus_minor`.
            $table->unsignedSmallInteger('target_jobs');
            $table->unsignedBigInteger('bonus_minor');
            $table->unsignedSmallInteger('max_awards_per_provider')->default(1);
            $table->unsignedBigInteger('budget_minor');
            $table->unsignedBigInteger('spent_minor')->default(0);
            $table->unsignedBigInteger('min_order_minor')->nullable();
            $table->decimal('min_rating', 3, 2)->nullable();
            $table->json('service_category_ids')->nullable();
            $table->json('city_ids')->nullable();
            // Fraud rule: jobs beyond this count for the same customer do not count towards the quest.
            $table->unsignedSmallInteger('max_jobs_per_customer')->default(3);
            $table->text('status_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('incentive_progress', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained('incentive_campaigns')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->unsignedSmallInteger('completed_jobs')->default(0);
            $table->unsignedSmallInteger('awards_count')->default(0);
            // in_progress | held (fraud review) | exhausted (budget) | rejected
            $table->string('status', 12)->default('in_progress');
            $table->json('flags')->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'provider_id']);
            $table->index('status');
        });

        Schema::create('incentive_awards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('progress_id')->constrained('incentive_progress')->restrictOnDelete();
            $table->foreignId('campaign_id')->constrained('incentive_campaigns')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['progress_id', 'sequence']);
        });

        // One row per completed booking handled by the growth hook (referrals, loyalty, incentives): the hook can run twice safely.
        Schema::create('growth_processed_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->timestamp('processed_at');
        });

        // Per (campaign, provider, customer) job count of the incentive fraud rule.
        Schema::create('incentive_customer_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('progress_id')->constrained('incentive_progress')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('jobs')->default(0);

            $table->unique(['progress_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        foreach (['incentive_customer_jobs', 'growth_processed_bookings', 'incentive_awards', 'incentive_progress', 'incentive_campaigns', 'loyalty_awards', 'loyalty_members', 'loyalty_levels', 'referral_rewards', 'referral_relationships', 'referral_codes', 'referral_campaigns'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
