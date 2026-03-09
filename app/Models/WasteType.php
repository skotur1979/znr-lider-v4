<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'waste_code',
        'name',
        'is_hazardous',
    ];

    protected $casts = [
        'is_hazardous' => 'boolean',
    ];

    public function ontoRecords(): HasMany
    {
        return $this->hasMany(OntoRecord::class, 'waste_type_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->waste_code} - {$this->name}";
    }
    public function getFormattedWasteCodeAttribute()
{
    if (!$this->waste_code) {
        return null;
    }

    $code = str_replace('*','',$this->waste_code);
    $formatted = substr($code,0,2).' '.substr($code,2,2).' '.substr($code,4,2);

    if (str_contains($this->waste_code,'*')) {
        $formatted .= '*';
    }

    return $formatted;
}
}