<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PPEEquipment extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        'Registar OZO';

    protected $table =
        'ppe_equipments';

    protected $fillable = [
        'user_id',
        'name',
        'standard',
        'duration_months',
        'is_active',
        'attachments',
    ];

    protected $casts = [
        'duration_months' =>
            'integer',

        'is_active' =>
            'boolean',

        'attachments' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | IMMUTABLE OWNERSHIP
    |--------------------------------------------------------------------------
    |
    | Kod novog zapisa user_id se smije postaviti.
    |
    | Nakon što zapis postoji, ownership se više
    | nikada ne smije promijeniti.
    |
    | Ova zaštita radi odmah pri fill()/update()/
    | forceFill(), prije saving/updating eventa.
    |
    */

    public function setUserIdAttribute(
        mixed $value
    ): void {
        $newOwnerId =
            $value !== null
                ? (int) $value
                : null;

        if ($this->exists) {
            $originalOwnerId =
                $this->getRawOriginal(
                    'user_id'
                );

            $originalOwnerId =
                $originalOwnerId !== null
                    ? (int) $originalOwnerId
                    : null;

            if (
                $newOwnerId
                    !== $originalOwnerId
            ) {
                throw ValidationException::withMessages([
                    'user_id' =>
                        'Organizacija OZO zapisa ne može se promijeniti.',
                ]);
            }
        }

        $this->attributes['user_id'] =
            $newOwnerId;
    }

    protected static function booted(): void
    {
        /*
         * Registar OZO više nema globalne zapise.
         */
        static::creating(
            function (
                PPEEquipment $equipment
            ): void {
                if (
                    (int) $equipment->user_id
                    <= 0
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'OZO zapis mora pripadati organizaciji.',
                    ]);
                }
            }
        );

        /*
         * Dodatna defense-in-depth zaštita.
         */
        static::updating(
            function (
                PPEEquipment $equipment
            ): void {
                if (
                    $equipment->isDirty(
                        'user_id'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Organizacija OZO zapisa ne može se promijeniti.',
                    ]);
                }
            }
        );

        /*
         * Ako je tijekom uređivanja uklonjen prilog,
         * brišemo i fizičku datoteku.
         */
        static::updated(
            function (
                PPEEquipment $equipment
            ): void {
                if (
                    ! $equipment->wasChanged(
                        'attachments'
                    )
                ) {
                    return;
                }

                $oldFiles =
                    static::normalizeAttachments(
                        $equipment->getRawOriginal(
                            'attachments'
                        )
                    );

                $newFiles =
                    static::normalizeAttachments(
                        $equipment->attachments
                    );

                $removed =
                    array_values(
                        array_diff(
                            $oldFiles,
                            $newFiles
                        )
                    );

                static::deleteAttachmentFiles(
                    $removed,
                    (int) $equipment->user_id
                );
            }
        );

        /*
         * OZO korišten u povijesti Upisnika OZO
         * nije moguće fizički izbrisati.
         */
        static::deleting(
            function (
                PPEEquipment $equipment
            ): void {
                if (
                    $equipment->isUsedInPPELogs()
                ) {
                    throw ValidationException::withMessages([
                        'name' =>
                            'OZO koji je korišten u Upisniku OZO nije moguće trajno izbrisati. Postavi ga kao neaktivan.',
                    ]);
                }
            }
        );

        /*
         * Kod dopuštenog trajnog brisanja
         * čistimo priloge i pripadajući QR zapis.
         */
        static::deleted(
            function (
                PPEEquipment $equipment
            ): void {
                static::deleteAttachmentFiles(
                    static::normalizeAttachments(
                        $equipment->attachments
                    ),
                    (int) $equipment->user_id
                );

                QrCode::query()
                    ->where(
                        'type',
                        'ppe_equipment'
                    )
                    ->where(
                        'qrable_type',
                        PPEEquipment::class
                    )
                    ->where(
                        'qrable_id',
                        $equipment->getKey()
                    )
                    ->delete();
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PPEItem::class,
            'ppe_equipment_id'
        );
    }

    public function isUsedInPPELogs(): bool
    {
        /*
         * Novi zapisi imaju direktnu FK vezu.
         *
         * Uzimamo u obzir i soft-deaktivirane
         * stavke jer predstavljaju povijest.
         */
        if (
            PPEItem::withTrashed()
                ->where(
                    'ppe_equipment_id',
                    $this->getKey()
                )
                ->exists()
        ) {
            return true;
        }

        /*
         * Legacy stavke prije uvođenja
         * ppe_equipment_id.
         */
        $legacyQuery =
            PPEItem::withTrashed()
                ->whereNull(
                    'ppe_equipment_id'
                )
                ->where(
                    'equipment_name',
                    $this->name
                )
                ->whereIn(
                    'personal_protective_equipment_log_id',
                    PPELog::withTrashed()
                        ->where(
                            'user_id',
                            $this->user_id
                        )
                        ->select(
                            'id'
                        )
                );

        if (
            filled(
                $this->standard
            )
        ) {
            $legacyQuery->where(
                function (
                    $query
                ): void {
                    $query
                        ->where(
                            'standard',
                            $this->standard
                        )
                        ->orWhereNull(
                            'standard'
                        )
                        ->orWhere(
                            'standard',
                            ''
                        );
                }
            );
        }

        return
            $legacyQuery->exists();
    }

    protected static function normalizeAttachments(
        mixed $attachments
    ): array {
        if (
            is_array(
                $attachments
            )
        ) {
            return array_values(
                array_filter(
                    $attachments,
                    fn ($path): bool =>
                        is_string(
                            $path
                        )
                        && filled(
                            $path
                        )
                )
            );
        }

        if (
            is_string(
                $attachments
            )
            && filled(
                $attachments
            )
        ) {
            $decoded =
                json_decode(
                    $attachments,
                    true
                );

            if (
                is_array(
                    $decoded
                )
            ) {
                return array_values(
                    array_filter(
                        $decoded,
                        fn ($path): bool =>
                            is_string(
                                $path
                            )
                            && filled(
                                $path
                            )
                    )
                );
            }
        }

        return [];
    }

    protected static function deleteAttachmentFiles(
        array $files,
        int $ownerId
    ): void {
        foreach (
            $files
            as $path
        ) {
            if (
                ! static::isSafeAttachmentPath(
                    $path,
                    $ownerId
                )
            ) {
                continue;
            }

            Storage::disk(
                'public'
            )->delete(
                $path
            );
        }
    }

    protected static function isSafeAttachmentPath(
        mixed $path,
        int $ownerId
    ): bool {
        if (
            ! is_string(
                $path
            )
            || blank(
                $path
            )
        ) {
            return false;
        }

        $path =
            str_replace(
                '\\',
                '/',
                $path
            );

        if (
            str_contains(
                $path,
                '../'
            )
            || str_contains(
                $path,
                "\0"
            )
        ) {
            return false;
        }

        /*
         * Legacy putanja.
         */
        $legacyPrefix =
            'ppe-equipment/attachments/';

        /*
         * Nova tenant-scoped putanja.
         */
        $ownerPrefix =
            'ppe-equipment/'
            . $ownerId
            . '/attachments/';

        return
            str_starts_with(
                $path,
                $legacyPrefix
            )
            || str_starts_with(
                $path,
                $ownerPrefix
            );
    }
}