<?php

namespace App\Modules\Web\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Platform feature and limit definition model.
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Web\Models\Plan> $plans
 */
class Feature extends Model
{
    use HasFactory;

    protected $table = 'features';

    protected $fillable = [
        'name',
        'key',
        'type',
    ];

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->using(PlanFeature::class)
            ->withPivot('value')
            ->withTimestamps();
    }

    public function isLimit(): bool
    {
        return $this->type === 'limit';
    }

    public function isFeature(): bool
    {
        return $this->type === 'feature';
    }

    public function scopeLimits($query)
    {
        return $query->where('type', 'limit');
    }

    public function scopeFeatures($query)
    {
        return $query->where('type', 'feature');
    }
}
