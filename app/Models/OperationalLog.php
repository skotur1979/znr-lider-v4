<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationalLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'log_date',
        'title',
        'note',
        'location',
        'type',
        'status',
        'attachments',
        'converted_type',
        'converted_id',
    ];

    protected $casts = [
        'log_date' => 'date',
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function converted(): MorphTo
    {
        return $this->morphTo();
    }

    public function isConverted(): bool
    {
        return filled($this->converted_type) && filled($this->converted_id);
    }
}
