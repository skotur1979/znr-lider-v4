<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PPELog extends Model
{
    use SoftDeletes;

    protected $table = 'personal_protective_equipment_logs';

    protected $fillable = [
        'user_id',
        'user_last_name',
        'user_oib',
        'workplace',
        'organization_unit',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PPEItem::class, 'personal_protective_equipment_log_id', 'id');
    }
}