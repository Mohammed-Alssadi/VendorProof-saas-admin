<?php

namespace App\Modules\Api\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCategoryRequirement extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $table = 'vendor_category_requirements';

    protected $fillable = [
        'tenant_id',
        'vendor_category_id',
        'document_type_id',
        'is_mandatory',
        'notes',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'vendor_category_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'vendor_category_id' => $this->vendor_category_id,
            'document_type_id' => $this->document_type_id,
            'is_mandatory' => (bool) $this->is_mandatory,
            'notes' => $this->notes,
            'document_type' => $this->relationLoaded('documentType') && $this->documentType
                ? $this->documentType->toApiArray()
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
