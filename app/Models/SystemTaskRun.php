<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemTaskRun extends Model
{
    protected $fillable = [
        'task_key',
        'task_name',
        'status',
        'last_started_at',
        'last_finished_at',
        'last_success_at',
        'last_failed_at',
        'processed_count',
        'duration_ms',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_started_at' => 'datetime',
            'last_finished_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failed_at' => 'datetime',
            'processed_count' => 'integer',
            'duration_ms' => 'integer',
            'metadata' => 'array',
        ];
    }
}
