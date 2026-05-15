<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteType extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Vrste otpada';

    protected $fillable = [
        'user_id',
        'waste_code',
        'name',
        'is_hazardous',
    ];

    protected $casts = [
        'is_hazardous' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ontoRecords(): HasMany
    {
        return $this->hasMany(OntoRecord::class, 'waste_type_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->formatted_waste_code} - {$this->name}";
    }

    public function getFormattedWasteCodeAttribute(): ?string
    {
        if (! $this->waste_code) {
            return null;
        }

        $code = str_replace('*', '', $this->waste_code);
        $formatted = substr($code, 0, 2) . ' ' . substr($code, 2, 2) . ' ' . substr($code, 4, 2);

        if (str_contains($this->waste_code, '*')) {
            $formatted .= '*';
        }

        return $formatted;
    }
}