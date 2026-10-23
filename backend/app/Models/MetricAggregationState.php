<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pre-aggregated reporting rows (P5.3, ADR-178), rebuilt by `metrics:aggregate-daily`; never edited by hand and never a source of truth.
 */
class MetricAggregationState extends Model
{
    protected $table = 'metrics_aggregation_state';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['metric_date' => 'date', 'computed_at' => 'datetime', 'last_aggregated_at' => 'datetime', 'covered_through' => 'date'];
    }
}
