<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class FirstAidKit extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        'Prva pomoć';

    protected $fillable = [
        'user_id',
        'location',
        'inspected_at',
        'note',
    ];

    protected $casts = [
        'inspected_at' => 'date',
    ];

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
                        'Organizacija ormarića prve pomoći ne može se promijeniti.',
                ]);
            }
        }

        $this->attributes['user_id'] =
            $newOwnerId;
    }

    protected static function booted(): void
    {
        static::creating(
            function (
                FirstAidKit $kit
            ): void {
                if (
                    (int) $kit->user_id
                    <= 0
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Ormarić prve pomoći mora pripadati organizaciji.',
                    ]);
                }
            }
        );

        static::updating(
            function (
                FirstAidKit $kit
            ): void {
                if (
                    $kit->isDirty(
                        'user_id'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Organizacija ormarića prve pomoći ne može se promijeniti.',
                    ]);
                }
            }
        );

        /*
         * Nakon trajnog brisanja ormarića
         * uklanjamo pripadajući QR zapis.
         */
        static::deleted(
            function (
                FirstAidKit $kit
            ): void {
                QrCode::query()
                    ->where(
                        'type',
                        'first_aid_kit'
                    )
                    ->where(
                        'qrable_type',
                        FirstAidKit::class
                    )
                    ->where(
                        'qrable_id',
                        $kit->getKey()
                    )
                    ->delete();
            }
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            FirstAidItem::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }
}