<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class WasteOrganization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'oib',
        'nkd_code',
        'contact_person',
        'contact_details',
        'registered_office',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function locations(): HasMany
    {
        return $this->hasMany(WasteOrganizationLocation::class, 'waste_organization_id');
    }

    public function ontoRecords(): HasMany
    {
        return $this->hasMany(OntoRecord::class, 'waste_organization_id');
    }
}
