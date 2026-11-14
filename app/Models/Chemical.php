<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Chemical extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'Kemikalije';

    protected $fillable = [
        'user_id',
        'product_name',
        'cas_number',
        'ufi_number',
        'hazard_pictograms',
        'h_statements',
        'p_statements',
        'usage_location',
        'annual_quantity',
        'gvi_kgvi',
        'voc',
        'stl_hzjz',
        'attachments',
    ];

    protected $casts = [
        'hazard_pictograms' =>
            'array',

        'h_statements' =>
            'array',

        'p_statements' =>
            'array',

        'attachments' =>
            'array',

        'stl_hzjz' =>
            'date',
    ];

    protected static function booted(): void
    {
        /*
         * Soft delete:
         * - ne brišemo QR
         * - ne brišemo priloge
         *
         * Time je restore moguć bez
         * gubitka povezanih podataka.
         *
         * Force delete:
         * - brišemo pripadajući QR zapis
         * - brišemo fizičke priloge ako ih
         *   više ne koristi drugi Chemical.
         */
        static::forceDeleted(
            function (
                Chemical $chemical
            ): void {
                QrCode::query()
                    ->where(
                        'type',
                        'chemical'
                    )
                    ->where(
                        'qrable_type',
                        Chemical::class
                    )
                    ->where(
                        'qrable_id',
                        $chemical
                            ->getKey()
                    )
                    ->delete();

                $attachments =
                    collect(
                        $chemical
                            ->attachments
                        ?? []
                    )
                        ->filter(
                            fn ($path) =>
                                is_string(
                                    $path
                                )
                                && filled(
                                    $path
                                )
                        )
                        ->map(
                            fn (
                                string $path
                            ) =>
                                self::
                                    normalizeAttachmentPath(
                                        $path
                                    )
                        )
                        ->filter(
                            fn (
                                string $path
                            ) =>
                                str_starts_with(
                                    $path,
                                    'chemicals/'
                                )
                        )
                        ->unique()
                        ->values();

                if (
                    $attachments
                        ->isEmpty()
                ) {
                    return;
                }

                /*
                 * Zbog starih preserveFilenames
                 * uploadova provjeravamo koristi li
                 * isti fizički path još neki zapis.
                 */
                $pathsUsedElsewhere =
                    static::withTrashed()
                        ->get([
                            'id',
                            'attachments',
                        ])
                        ->flatMap(
                            fn (
                                Chemical $other
                            ) =>
                                collect(
                                    $other
                                        ->attachments
                                    ?? []
                                )
                        )
                        ->filter(
                            fn ($path) =>
                                is_string(
                                    $path
                                )
                                && filled(
                                    $path
                                )
                        )
                        ->map(
                            fn (
                                string $path
                            ) =>
                                self::
                                    normalizeAttachmentPath(
                                        $path
                                    )
                        )
                        ->unique()
                        ->flip();

                foreach (
                    $attachments as $path
                ) {
                    if (
                        $pathsUsedElsewhere
                            ->has(
                                $path
                            )
                    ) {
                        continue;
                    }

                    if (
                        Storage::disk(
                            'public'
                        )->exists(
                            $path
                        )
                    ) {
                        Storage::disk(
                            'public'
                        )->delete(
                            $path
                        );
                    }
                }
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    private static function normalizeAttachmentPath(
        string $path
    ): string {
        return str_replace(
            '\\',
            '/',
            ltrim(
                trim(
                    $path
                ),
                '/'
            )
        );
    }
}