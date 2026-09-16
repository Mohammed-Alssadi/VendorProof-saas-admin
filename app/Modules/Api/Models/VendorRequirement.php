<?php

namespace App\Modules\Api\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRequirement extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    public const STATUS_MISSING = 'missing';

    public const STATUS_PENDING = 'pending_review';

    public const STATUS_VALID = 'valid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WAIVED = 'waived';

    protected $table = 'vendor_requirements';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'document_type_id',
        'is_mandatory',
        'status',
        'waived_at',
        'waived_reason',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_MISSING,
        'is_mandatory' => true,
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'waived_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * Determine whether the requirement is currently waived.
     */
    public function isWaived(): bool
    {
        return $this->status === self::STATUS_WAIVED;
    }

    /**
     * Mark requirement as administratively waived with a mandatory reason.
     */
    public function waive(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_WAIVED,
            'waived_at' => now(),
            'waived_reason' => $reason,
        ]);
    }

    /**
     * Revoke administrative waiver and return requirement to missing status.
     */
    public function unwaive(): void
    {
        $this->update([
            'status' => self::STATUS_MISSING,
            'waived_at' => null,
            'waived_reason' => null,
        ]);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'document_type_id' => $this->document_type_id,
            'is_mandatory' => (bool) $this->is_mandatory,
            'status' => $this->status,
            'waived_at' => $this->waived_at?->toISOString(),
            'waived_reason' => $this->waived_reason,
            'notes' => $this->notes,
            'document_type' => $this->relationLoaded('documentType') && $this->documentType
                ? $this->documentType->toApiArray()
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
