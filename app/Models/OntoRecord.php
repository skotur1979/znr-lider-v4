<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class OntoRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'waste_organization_id',
        'waste_organization_location_id',
        'waste_type_id',
        'year',
        'responsible_person',
        'opening_date',
        'closing_date',
        'current_balance_kg',
        'is_closed',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'opening_date' => 'date',
        'closing_date' => 'date',
        'current_balance_kg' => 'decimal:2',
        'is_closed' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (blank($record->user_id)) {
                $record->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(WasteOrganization::class, 'waste_organization_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WasteOrganizationLocation::class, 'waste_organization_location_id');
    }

    public function wasteType(): BelongsTo
    {
        return $this->belongsTo(WasteType::class, 'waste_type_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(OntoEntry::class, 'onto_record_id')
            ->orderBy('entry_date')
            ->orderBy('entry_no');
    }

    public function trackingForms(): HasMany
    {
        return $this->hasMany(WasteTrackingForm::class, 'onto_record_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $location = $this->location?->name ?? '-';
        $waste = $this->wasteType?->waste_code ?? '-';

        return "{$location} / {$waste} / {$this->year}";
    }
}       
