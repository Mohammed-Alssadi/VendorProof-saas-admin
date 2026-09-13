<?php

namespace App\Models;

use App\Modules\Api\Models\User;
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
 * Tenant model representing a subscriber company.
 *
 * @property string $id
 * @property int|null $plan_id
 * @property string $name
 * @property string|null $slug
 * @property string $email
 * @property string|null $phone
 * @property string|null $company_cr
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Modules\Web\Models\Plan|null $plan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Api\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Web\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \App\Modules\Web\Models\Subscription|null $currentSubscription
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

    /**
     * باقة المنشأة المباشرة (Direct Freemium Plan).
     */
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

    /**
     * فحص هل المنشأة نشطة تشغيلياً.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * تحويل بيانات المنشأة وباقة اشتراكها إلى مصفوفة معيارية للـ API.
     *
     * @return array<string, mixed>
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
