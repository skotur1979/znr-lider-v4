<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fire extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'place',
        'type',

        // ✅ ALIAS koji Filament koristi:
        'factory_number_year_of_production',

        'serial_label_number',
        'examination_valid_from',
        'examination_valid_until',
        'regular_examination_valid_from',
        'service',
        'visible',
        'remark',
        'action',
        'pdf',
    ];

    protected $casts = [
        'examination_valid_from' => 'date',
        'examination_valid_until' => 'date',
        'regular_examination_valid_from' => 'date',
        'pdf' => 'array',
    ];

    /**
     * ✅ Mapira "factory_number_year_of_production" (u formi/filamentu)
     * na stvarni DB stupac "factory_number/year_of_production"
     */
    protected function factoryNumberYearOfProduction(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['factory_number/year_of_production'] ?? null,
            set: fn ($value) => ['factory_number/year_of_production' => $value],
        );
    }
}
