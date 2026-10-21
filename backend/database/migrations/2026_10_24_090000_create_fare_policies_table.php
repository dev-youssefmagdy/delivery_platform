<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference-price configuration (SRS §9.1): guidance for the customer, never a quote and never a multiplier of
        // demand. A booking copies the policy it was priced with into its own snapshot, so editing a row here never
        // changes history (Plan I4).
        Schema::create('fare_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->restrictOnDelete();
            // Null = the whole market; a zone policy wins over the category-wide one inside that zone.
            $table->foreignId('service_zone_id')->nullable()->constrained('service_zones')->restrictOnDelete();
            $table->unsignedSmallInteger('formula_version')->default(1);
            $table->char('currency', 3);
            $table->unsignedBigInteger('base_minor')->default(0);
            $table->unsignedBigInteger('per_km_minor')->default(0);
            $table->unsignedBigInteger('per_min_minor')->default(0);
            $table->unsignedBigInteger('min_fare_minor')->default(0);
            $table->unsignedBigInteger('stop_fee_minor')->default(0);
            // [{field, mode: fixed|per_unit, amount_minor, match?, label: {ar, en}}]
            $table->json('attribute_surcharges')->nullable();
            // Round recommended / low / high to a multiple of this many minor units (100 = whole pounds).
            $table->unsignedInteger('rounding_step_minor')->default(100);
            // Low and high suggestions as basis points of the recommended price (8500 = 85%).
            $table->unsignedInteger('range_low_bp')->default(8500);
            $table->unsignedInteger('range_high_bp')->default(12500);
            $table->boolean('active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['service_category_id', 'service_zone_id', 'active', 'effective_from'], 'fare_policies_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_policies');
    }
};
