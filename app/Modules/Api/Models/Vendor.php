<?php

namespace App\Modules\Api\Models;

use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'tenant_id',
        'vendor_category_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requirements()
    {
        return $this->hasMany(VendorRequirement::class, 'vendor_id');
    }

    /**
     * Synchronize and auto-provision requirements from the assigned vendor category.
     * Idempotently creates missing requirements without overwriting existing records.
     */
    public function syncCategoryRequirements(): void
    {
        if (! $this->vendor_category_id) {
            return;
        }

        $categoryRequirements = VendorCategoryRequirement::where('vendor_category_id', $this->vendor_category_id)
            ->where('tenant_id', $this->tenant_id)
            ->get();

        foreach ($categoryRequirements as $catReq) {
            $this->requirements()->firstOrCreate(
                ['document_type_id' => $catReq->document_type_id],
                [
                    'tenant_id' => $this->tenant_id,
                    'is_mandatory' => $catReq->is_mandatory,
                    'notes' => $catReq->notes,
                    'status' => VendorRequirement::STATUS_MISSING,
                ]
            );
        }
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Serialize vendor data for API responses. Excludes internal tenant_id.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
