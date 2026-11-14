<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PPELog extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'OZO upisnik';

    protected $table =
        'personal_protective_equipment_logs';

    protected $fillable = [
        'user_id',
        'user_last_name',
        'user_oib',
        'workplace',
        'organization_unit',
    ];

    protected static function booted(): void
    {
        /*
         * Kod force deletea roditelja prvo fizički
         * brišemo stavke kroz Eloquent.
         *
         * Time se aktivira PPEItem::forceDeleted()
         * i fizički brišu datoteke potpisa.
         *
         * Kod običnog soft deletea Upisnika
         * stavke i potpisi ostaju sačuvani.
         */
        static::forceDeleting(
            function (self $log): void {
                $log
                    ->items()
                    ->withTrashed()
                    ->get()
                    ->each(
                        fn (PPEItem $item) =>
                            $item->forceDelete()
                    );
            }
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PPEItem::class,
            'personal_protective_equipment_log_id',
            'id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }
}
