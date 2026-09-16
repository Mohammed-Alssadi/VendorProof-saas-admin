<?php

namespace App\Models;

use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'plan_price_id',
        'amount',
        'currency',
        'billing_cycle',
        'status',
        'gateway',
        'gateway_reference_id',
        'payment_method',
        'receipt_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function formattedAmount(): string
    {
        $symbol = PlanPrice::getCurrencySymbol($this->currency);

        return number_format((float) $this->amount, 2).' '.$symbol;
    }

    public function statusLabelArabic(): string
    {
        return match ($this->status) {
            'paid' => 'مسددة',
            'pending' => 'بانتظار السداد',
            'failed' => 'تعثر السداد',
            'refunded' => 'مستردة',
            default => $this->status,
        };
    }

    public function billingCycleArabic(): string
    {
        return $this->billing_cycle === 'yearly' ? 'سنوي' : 'شهري';
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
