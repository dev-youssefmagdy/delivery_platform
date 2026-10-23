<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Daily metrics (P5.3, ADR-178). Pre-aggregated heavy KPIs, rebuilt idempotently by `metrics:aggregate-daily`: a run replaces the rows of a
| (market day, city) instead of adding to them. `metric_date` is the day in the CITY's timezone (zone / category 0 = none). Money is integer
| minor units per currency. Nothing here is a source of truth: every row can be recomputed from bookings, offers, payments and the ledger.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics_daily_bookings', function (Blueprint $table): void {
            $table->id();
            $table->date('metric_date');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('service_zone_id')->default(0);
            $table->unsignedBigInteger('service_category_id')->default(0);
            $table->char('currency', 3);
            // Cohort of requests created on the market day (drafts excluded).
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('with_offers')->default(0);
            $table->unsignedInteger('selected')->default(0);
            $table->unsignedInteger('completed')->default(0);
            $table->unsignedInteger('cancelled_customer')->default(0);
            $table->unsignedInteger('cancelled_provider')->default(0);
            $table->unsignedInteger('cancelled_operations')->default(0);
            $table->unsignedInteger('expired')->default(0);
            $table->unsignedInteger('no_show')->default(0);
            $table->unsignedBigInteger('proposed_fare_sum_minor')->default(0);
            $table->unsignedBigInteger('agreed_fare_sum_minor')->default(0);
            $table->unsignedBigInteger('estimated_gmv_minor')->default(0);
            $table->unsignedInteger('offers_submitted')->default(0);
            $table->unsignedInteger('offers_countered')->default(0);
            $table->unsignedBigInteger('offered_fare_sum_minor')->default(0);
            $table->unsignedInteger('recipients_notified')->default(0);
            $table->unsignedInteger('recipients_responded')->default(0);
            $table->unsignedBigInteger('response_seconds_sum')->default(0);
            $table->unsignedInteger('response_seconds_count')->default(0);
            $table->timestamp('computed_at');

            $table->unique(['metric_date', 'city_id', 'service_zone_id', 'service_category_id', 'currency'], 'metrics_daily_bookings_key');
            $table->index(['metric_date', 'service_category_id']);
        });

        Schema::create('metrics_daily_finance', function (Blueprint $table): void {
            $table->id();
            $table->date('metric_date');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('service_category_id')->default(0);
            $table->char('currency', 3);
            // Payment records, by the day the payment was paid / failed / refunded.
            $table->unsignedInteger('payments_paid')->default(0);
            $table->unsignedBigInteger('payments_paid_minor')->default(0);
            $table->unsignedInteger('payments_failed')->default(0);
            $table->unsignedBigInteger('payments_failed_minor')->default(0);
            $table->unsignedBigInteger('refunds_minor')->default(0);
            // Ledger lines of booking settlements, by posting day.
            $table->bigInteger('commission_minor')->default(0);
            $table->bigInteger('provider_earnings_minor')->default(0);
            // Coupon redemptions (committed), by redemption day.
            $table->unsignedInteger('coupon_redemptions')->default(0);
            $table->unsignedBigInteger('coupon_discount_minor')->default(0);
            $table->unsignedBigInteger('coupon_platform_minor')->default(0);
            $table->unsignedBigInteger('coupon_fare_minor')->default(0);
            $table->timestamp('computed_at');

            $table->unique(['metric_date', 'city_id', 'service_category_id', 'currency'], 'metrics_daily_finance_key');
            $table->index(['metric_date', 'currency']);
        });

        Schema::create('metrics_daily_operations', function (Blueprint $table): void {
            $table->id();
            $table->date('metric_date');
            $table->string('timezone', 64);
            $table->unsignedInteger('tickets_opened')->default(0);
            $table->unsignedInteger('tickets_resolved')->default(0);
            $table->unsignedInteger('tickets_sla_breached')->default(0);
            $table->unsignedBigInteger('first_response_seconds_sum')->default(0);
            $table->unsignedInteger('first_response_count')->default(0);
            $table->unsignedInteger('incidents_opened')->default(0);
            $table->unsignedInteger('incidents_urgent')->default(0);
            $table->unsignedInteger('incidents_resolved')->default(0);
            $table->unsignedInteger('incidents_sla_breached')->default(0);
            $table->unsignedInteger('new_customers')->default(0);
            $table->unsignedInteger('new_providers')->default(0);
            $table->unsignedInteger('active_customers')->default(0);
            $table->timestamp('computed_at');

            $table->unique(['metric_date', 'timezone'], 'metrics_daily_operations_key');
        });

        // One row per metric set: when it was last rebuilt and how far it reaches (the "data as of" indicator).
        Schema::create('metrics_aggregation_state', function (Blueprint $table): void {
            $table->id();
            $table->string('metric_set', 24)->unique();
            $table->timestamp('last_aggregated_at')->nullable();
            $table->date('covered_through')->nullable();
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics_aggregation_state');
        Schema::dropIfExists('metrics_daily_operations');
        Schema::dropIfExists('metrics_daily_finance');
        Schema::dropIfExists('metrics_daily_bookings');
    }
};
