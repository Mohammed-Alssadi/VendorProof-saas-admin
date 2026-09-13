<?php

namespace App\Modules\Web\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan pricing tier model (monthly / yearly).
 *
 * @property int $id
 * @property int $plan_id
 * @property string $price
 * @property string $currency
 * @property string $billing_cycle
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \App\Modules\Web\Models\Plan $plan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Web\Models\Subscription> $subscriptions
 */
class PlanPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plan_prices';

    protected $fillable = [
        'plan_id',
        'price',
        'currency',
        'billing_cycle',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * ترجمة كود الآيزو للعملة إلى رمز العرض المالي الاحترافي.
     */
    public static function getCurrencySymbol(?string $currency): string
    {
        return match (strtoupper($currency ?? 'SAR')) {
            'SAR' => 'ر.س',
            'USD' => '$',
            'EUR' => '€',
            'AED' => 'د.إ',
            'GBP' => '£',
            'KWD' => 'د.ك',
            'BHD' => 'د.ب',
            'QAR' => 'ر.ق',
            'OMR' => 'ر.ع',
            default => $currency ?? 'ر.س',
        };
    }

    /**
     * خاصية مشتقة للحصول على رمز العملة الفعلي لهذا السعر.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return self::getCurrencySymbol($this->currency);
    }

    /**
     * Return formatted price string with dynamic currency symbol.
     */
    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 2) . ' ' . $this->currency_symbol;
    }
}
