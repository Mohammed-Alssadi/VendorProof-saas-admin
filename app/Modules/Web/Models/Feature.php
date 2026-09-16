<?php

namespace App\Modules\Web\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Platform feature and limit definition model.
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Plan> $plans
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
}
