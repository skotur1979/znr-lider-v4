<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'manufacturer',
        'factory_number',
        'inventory_number',
        'location',
        'examination_valid_from',
        'examination_valid_until',
        'examined_by',
        'report_number',
        'remark',
        'pdf',
    ];

    protected $casts = [
        'examination_valid_from' => 'date',
        'examination_valid_until' => 'date',
        'pdf' => 'array', // <-- KLJUČNO
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
