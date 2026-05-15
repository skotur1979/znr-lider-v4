<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationItem extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Dokumentacija';

    protected $fillable = [
        'user_id',
        'naziv',
        'tvrtka',
        'datum_izrade',
        'status_napomena',
        'prilozi',
    ];

    protected $casts = [
        'datum_izrade' => 'date',
        'prilozi' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}