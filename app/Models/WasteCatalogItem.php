<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class WasteCatalogItem extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Katalog otpada';

    protected $fillable = [
        'waste_code',
        'name',
        'is_hazardous',
        'record_mark',
    ];

    protected $casts = [
        'is_hazardous' => 'boolean',
    ];

    public function getFormattedWasteCodeAttribute(): string
    {
        if (! $this->waste_code) {
            return '-';
        }

        $raw = trim($this->waste_code);
        $hasStar = str_contains($raw, '*');
        $code = str_replace('*', '', $raw);
        $digits = preg_replace('/\D+/', '', $code);

        if (strlen($digits) >= 6) {
            $formatted = substr($digits, 0, 2) . ' ' . substr($digits, 2, 2) . ' ' . substr($digits, 4, 2);
        } else {
            $formatted = $raw;
        }

        return $hasStar ? $formatted . '*' : $formatted;
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->formatted_waste_code} - {$this->name}";
    }
}