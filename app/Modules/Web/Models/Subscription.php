<?php

namespace App\Modules\Web\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * Tenant subscription contract model.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $plan_price_id
 * @property string $status
 * @property Carbon $starts_at
 * @property Carbon|null $current_period_end
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read PlanPrice $planPrice
 */
class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan_price_id',
        'status',
        'starts_at',
        'current_period_end',
        'trial_ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            PlanPrice::class,
            'id',            // Foreign key on plan_prices table
            'id',            // Foreign key on plans table
            'plan_price_id', // Local key on subscriptions table
            'plan_id'        // Local key on plan_prices table
        );
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->current_period_end === null || $this->current_period_end->isFuture();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
}
