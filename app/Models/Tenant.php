<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Modules\Api\Models\User;
use App\Modules\Api\Models\Vendors\Vendor;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\Subscription;
use App\Traits\HasPlanFeatures;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a subscriber company (tenant) on the platform.
 */
class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasPlanFeatures;

    protected $table = 'tenants';

    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'email',
        'phone',
        'company_cr',
        'status',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Serialize tenant data for API responses. Resolves the effective plan including expiry fallback.
     */
    public function toApiArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'company_cr' => $this->company_cr,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'plan'       => $this->currentPlan()?->toApiArray(),
        ];
    }
}
