<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentationItem extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        'Dokumentacija';

    protected $fillable = [
        'user_id',
        'naziv',
        'tvrtka',
        'datum_izrade',
        'status_napomena',
        'prilozi',
    ];

    protected $casts = [
        'datum_izrade' =>
            'date',

        'prilozi' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | IMMUTABLE OWNERSHIP
    |--------------------------------------------------------------------------
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
                        'Organizacija dokumentacije ne može se promijeniti.',
                ]);
            }
        }

        $this->attributes['user_id'] =
            $newOwnerId;
    }

    protected static function booted(): void
    {
        /*
         * Svaki zapis mora pripadati organizaciji.
         */
        static::creating(
            function (
                DocumentationItem $item
            ): void {
                if (
                    (int) $item->user_id
                    <= 0
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Dokumentacija mora pripadati organizaciji.',
                    ]);
                }
            }
        );

        /*
         * Dodatna defense-in-depth zaštita ownershipa.
         */
        static::updating(
            function (
                DocumentationItem $item
            ): void {
                if (
                    $item->isDirty(
                        'user_id'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Organizacija dokumentacije ne može se promijeniti.',
                    ]);
                }
            }
        );

        /*
         * Ako je korisnik uklonio prilog tijekom edita,
         * fizičku datoteku uklanjamo tek nakon uspješnog
         * UPDATE-a baze.
         */
        static::updated(
            function (
                DocumentationItem $item
            ): void {
                if (
                    ! $item->wasChanged(
                        'prilozi'
                    )
                ) {
                    return;
                }

                $oldFiles =
                    static::normalizeAttachments(
                        $item->getRawOriginal(
                            'prilozi'
                        )
                    );

                $newFiles =
                    static::normalizeAttachments(
                        $item->prilozi
                    );

                $removedFiles =
                    array_values(
                        array_diff(
                            $oldFiles,
                            $newFiles
                        )
                    );

                static::deleteAttachmentFiles(
                    $removedFiles,
                    (int) $item->user_id,
                    $item->getKey()
                );
            }
        );

        /*
         * Dokumentacija nema SoftDeletes.
         *
         * Nakon trajnog brisanja brišemo:
         * - pripadajuće datoteke
         * - pripadajući QR zapis.
         */
        static::deleted(
            function (
                DocumentationItem $item
            ): void {
                static::deleteAttachmentFiles(
                    static::normalizeAttachments(
                        $item->prilozi
                    ),
                    (int) $item->user_id,
                    $item->getKey()
                );

                QrCode::query()
                    ->where(
                        'type',
                        'documentation'
                    )
                    ->where(
                        'qrable_type',
                        DocumentationItem::class
                    )
                    ->where(
                        'qrable_id',
                        $item->getKey()
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

    public static function attachmentDirectory(
        ?DocumentationItem $record = null
    ): string {
        $user =
            auth()->user();

        $ownerId =
            $record?->user_id;

        if (
            ! $ownerId
            && $user
            && ! $user->isSuperAdmin()
        ) {
            $ownerId =
                $user->ownerId();
        }

        if (
            ! $ownerId
        ) {
            throw ValidationException::withMessages([
                'prilozi' =>
                    'Nije moguće odrediti organizaciju za spremanje priloga.',
            ]);
        }

        return
            'documentation/'
            . (int) $ownerId
            . '/attachments';
    }

    public static function isSafeAttachmentPath(
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
                trim($path)
            );

        $path =
            ltrim(
                $path,
                '/'
            );

        if (
            $path === ''
            || str_contains(
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
         * Legacy dokumentacija.
         */
        if (
            str_starts_with(
                $path,
                'pdfs/'
            )
        ) {
            return true;
        }

        /*
         * Novi tenant-scoped direktorij.
         */
        return str_starts_with(
            $path,
            'documentation/'
            . $ownerId
            . '/attachments/'
        );
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
                        is_string($path)
                        && filled($path)
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
                            is_string($path)
                            && filled($path)
                    )
                );
            }
        }

        return [];
    }

    protected static function deleteAttachmentFiles(
        array $files,
        int $ownerId,
        ?int $recordId = null
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

            /*
             * Legacy zaštita:
             * ako ista fizička datoteka postoji u još jednom
             * zapisu, ne brišemo je.
             */
            if (
                static::attachmentReferencedElsewhere(
                    $path,
                    $recordId
                )
            ) {
                continue;
            }

            $disk =
                Storage::disk(
                    'public'
                );

            if (
                $disk->exists(
                    $path
                )
            ) {
                $disk->delete(
                    $path
                );
            }
        }
    }

    protected static function attachmentReferencedElsewhere(
        string $path,
        ?int $recordId
    ): bool {
        $query =
            static::query();

        if (
            $recordId
        ) {
            $query->whereKeyNot(
                $recordId
            );
        }

        return $query
            ->get([
                'id',
                'prilozi',
            ])
            ->contains(
                fn (
                    DocumentationItem $item
                ): bool =>
                    in_array(
                        $path,
                        static::normalizeAttachments(
                            $item->prilozi
                        ),
                        true
                    )
            );
    }
}