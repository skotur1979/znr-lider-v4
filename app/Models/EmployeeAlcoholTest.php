<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAlcoholTest extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'test_date',
        'result',
        'tested_by',
        'note',
    ];

    protected $casts = [
        'test_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}