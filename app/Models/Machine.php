<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Radna oprema';

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
        'pdf' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}