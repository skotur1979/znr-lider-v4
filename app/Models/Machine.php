<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function qrCode(): MorphOne
    {
        return $this
            ->morphOne(
                QrCode::class,
                'qrable'
            )
            ->where('type', 'machine');
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (Machine $machine): void {
            QrCode::query()
                ->where('type', 'machine')
                ->where(
                    'qrable_type',
                    static::class
                )
                ->where(
                    'qrable_id',
                    $machine->getKey()
                )
                ->delete();
        });
    }
}