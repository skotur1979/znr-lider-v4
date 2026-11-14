<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PPEItem extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'OZO';

    protected $table =
        'personal_protective_equipment_items';

    protected $fillable = [
        'personal_protective_equipment_log_id',
        'ppe_equipment_id',
        'equipment_name',
        'standard',
        'size',
        'duration_months',
        'issue_date',
        'end_date',
        'return_date',
        'signature',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'duration_months' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        /*
         * Ako se potpis zamijeni novim, stari fizički
         * zapis uklanjamo nakon uspješnog UPDATE-a.
         */
        static::updated(
            function (self $item): void {
                if (! $item->wasChanged('signature')) {
                    return;
                }

                static::deleteSignatureFile(
                    $item->getOriginal('signature')
                );
            }
        );

        /*
         * Soft delete čuva potpis i povijest.
         * Datoteku brišemo samo kod force deletea.
         */
        static::forceDeleted(
            function (self $item): void {
                static::deleteSignatureFile(
                    $item->signature
                );
            }
        );
    }

    protected static function deleteSignatureFile(
        ?string $signature
    ): void {
        if (
            blank($signature)
            || str_starts_with(
                $signature,
                'data:image'
            )
        ) {
            return;
        }

        $path = str_replace(
            '\\',
            '/',
            trim($signature)
        );

        $path = ltrim(
            $path,
            '/'
        );

        /*
         * Smije brisati samo OZO potpise.
         */
        if (
            $path === ''
            || ! str_starts_with(
                $path,
                'ozo-signatures/'
            )
            || preg_match(
                '#(^|/)\.\.(/|$)#',
                $path
            ) === 1
        ) {
            return;
        }

        $disk =
            Storage::disk('public');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(
            PPELog::class,
            'personal_protective_equipment_log_id',
            'id'
        );
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(
            PPEEquipment::class,
            'ppe_equipment_id'
        );
    }
}