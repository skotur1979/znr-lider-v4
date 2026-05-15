<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationalLog extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Operativni dnevnik';

    protected $fillable = [
        'user_id',
        'log_date',
        'title',
        'note',
        'items',
        'location',
        'type',
        'status',
        'attachments',
        'converted_type',
        'converted_id',
    ];

    protected $casts = [
        'log_date' => 'date',
        'items' => 'array',
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

    public function itemsCount(): int
    {
        return collect($this->items ?? [])
            ->filter(fn ($item) => filled($item['note'] ?? null))
            ->count();
    }

    public function tasksCount(): int
    {
        return collect($this->items ?? [])
            ->filter(fn ($item) => ! empty($item['create_task']))
            ->count();
    }
}
