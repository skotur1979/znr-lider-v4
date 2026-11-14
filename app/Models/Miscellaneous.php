<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Concerns\LogsActivity;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Miscellaneous extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Ostala ispitivanja';

    protected $table = 'miscellaneouses';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'examiner',
        'report_number',
        'examination_valid_from',
        'examination_valid_until',
        'remark',
        'pdf',
    ];

    protected $casts = [
        'examination_valid_from' => 'date',
        'examination_valid_until' => 'date',
        'pdf' => 'array',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(
            function (
                Miscellaneous $miscellaneous
            ): void {
                QrCode::query()
                    ->where(
                        'type',
                        'miscellaneous'
                    )
                    ->where(
                        'qrable_type',
                        Miscellaneous::class
                    )
                    ->where(
                        'qrable_id',
                        $miscellaneous->getKey()
                    )
                    ->delete();
            }
        );
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}