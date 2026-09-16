<?php

namespace App\Modules\Web\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Plan Feature pivot model representing plan-feature configuration and limit value.
 *
 * @property int $id
 * @property int $plan_id
 * @property int $feature_id
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Plan $plan
 * @property-read Feature $feature
 */
class PlanFeature extends Pivot
{
    protected $table = 'plan_features';

    public $incrementing = true;

    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
