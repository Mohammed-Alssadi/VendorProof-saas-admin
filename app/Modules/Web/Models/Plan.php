<?php

namespace App\Modules\Web\Models;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function monthlyPrice(): HasOne
    {
        return $this->hasOne(PlanPrice::class)->where('billing_cycle', 'monthly');
    }

    public function yearlyPrice(): HasOne
    {
        return $this->hasOne(PlanPrice::class)->where('billing_cycle', 'yearly');
    }

    public function activePriceFor(string $cycle): ?PlanPrice
    {
        return $this->prices()
            ->where('billing_cycle', $cycle)
            ->where('status', 'active')
            ->first();
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->using(PlanFeature::class)
            ->withPivot('value')
            ->withTimestamps();
    }

    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(Subscription::class, PlanPrice::class);
    }

    /**
     * Check if a feature is enabled for this plan.
     */
    public function hasFeature(string $featureKey): bool
    {
        $feature = $this->features->firstWhere('key', $featureKey);

        return $feature && $feature->pivot->value === '1';
    }

    /**
     * Get the limit value for a given feature key.
     */
    public function getLimit(string $featureKey): ?string
    {
        $feature = $this->features->firstWhere('key', $featureKey);

        return $feature ? $feature->pivot->value : '0';
    }

    /**
     * Determine if a limit is configured as unlimited (-1).
     */
    public function isUnlimited(string $featureKey): bool
    {
        $limit = $this->getLimit($featureKey);

        return $limit === '-1' || $limit === 'unlimited';
    }

    /**
     * Get the human-readable limit display value.
     */
    public function getLimitDisplay(string $featureKey, string $unlimitedLabel = 'غير محدود'): string
    {
        if ($this->isUnlimited($featureKey)) {
            return $unlimitedLabel;
        }

        $limit = $this->getLimit($featureKey);

        return $limit !== null ? (string) $limit : 'غير محدد';
    }

    public function getCurrencyAttribute(): string
    {
        return $this->monthlyPrice?->currency ?? $this->yearlyPrice?->currency ?? 'SAR';
    }

    public function getCurrencySymbolAttribute(): string
    {
        return PlanPrice::getCurrencySymbol($this->currency);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTenantsCountAttribute(): int
    {
        return (int) ($this->attributes['tenants_count'] ?? $this->tenants()->count());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function defaultFree(): ?self
    {
        return static::where('slug', 'free')->first()
            ?? static::where('status', 'active')->first();
    }

    public function isFree(): bool
    {
        if ($this->slug === 'free') {
            return true;
        }

        $prices = $this->relationLoaded('prices') ? $this->prices : $this->prices()->get();

        if ($prices->isEmpty()) {
            return false;
        }

        return $prices->every(fn ($p) => (float) $p->price === 0.0);
    }

    public function isPaid(): bool
    {
        return ! $this->isFree();
    }

    public function scopeFree($query)
    {
        return $query->where('slug', 'free');
    }

    public function scopePaid($query)
    {
        return $query->where('slug', '!=', 'free');
    }

    /**
     * @return array<string, int>
     */
    public function dynamicLimits(): array
    {
        $limits = [];

        foreach ($this->features as $feature) {
            if ($feature->type !== 'limit') {
                continue;
            }

            $value = $feature->pivot->value;

            if ($value === '-1' || $value === 'unlimited') {
                $limits[$feature->key] = -1;
                continue;
            }

            $numericValue = (int) $value;

            if ($numericValue > 0) {
                $limits[$feature->key] = $numericValue;
            }
        }

        return $limits;
    }

    /**
     * @return array<string>
     */
    public function dynamicFeatures(): array
    {
        return $this->features
            ->where('type', 'feature')
            ->filter(fn ($f) => $f->pivot->value === '1')
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return array<string>
     */
    public function checklist(): array
    {
        $items = [];

        foreach ($this->features as $feature) {
            $value = (string) ($feature->pivot->value ?? '0');

            if ($value === '0') {
                continue;
            }

            if ($feature->type === 'limit') {
                $limitDisplay = ($value === '-1' || $value === 'unlimited') ? 'غير محدود' : $value;
                $items[] = "{$limitDisplay} {$feature->name}";
            } else {
                $items[] = $feature->name;
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'limits'      => $this->dynamicLimits(),
            'features'    => $this->dynamicFeatures(),
        ];
    }
}
