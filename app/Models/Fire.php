<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fire extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Vatrogasni aparati';

    protected $fillable = [
        'user_id',
        'place',
        'type',
        'factory_number_year_of_production',
        'serial_label_number',
        'examination_valid_from',
        'examination_valid_until',
        'regular_examination_valid_from',
        'service',
        'visible',
        'remark',
        'action',
        'pdf',
    ];

    protected $casts = [
        'examination_valid_from' => 'date',
        'examination_valid_until' => 'date',
        'regular_examination_valid_from' => 'date',
        'pdf' => 'array',
    ];

    protected function regularExaminationValidUntil(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->regular_examination_valid_from) {
                    return null;
                }

                return $this->regular_examination_valid_from
                    ->copy()
                    ->addMonthsNoOverflow(3);
            },
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
