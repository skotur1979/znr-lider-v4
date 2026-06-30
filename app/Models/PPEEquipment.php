<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PPEEquipment extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Registar OZO';

    protected $table = 'ppe_equipments';

    protected $fillable = [
        'user_id',
        'name',
        'standard',
        'duration_months',
        'is_active',
        'attachments',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'is_active' => 'boolean',
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}