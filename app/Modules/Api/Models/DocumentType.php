<?php

namespace App\Modules\Api\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $table = 'document_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'requires_expiry',
        'is_active',
    ];

    protected $casts = [
        'requires_expiry' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function categoryRequirements()
    {
        return $this->hasMany(VendorCategoryRequirement::class, 'document_type_id');
    }

    public function vendorRequirements()
    {
        return $this->hasMany(VendorRequirement::class, 'document_type_id');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'requires_expiry' => (bool) $this->requires_expiry,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
