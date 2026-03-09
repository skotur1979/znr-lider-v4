<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteOrganizationLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'waste_organization_id',
        'name',
        'unit_code',
        'internal_code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(WasteOrganization::class, 'waste_organization_id');
    }

    public function ontoRecords(): HasMany
    {
        return $this->hasMany(OntoRecord::class, 'waste_organization_location_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->name,
            $this->internal_code ? '(' . $this->internal_code . ')' : null,
        ]);

        return implode(' ', $parts);
    }
}
