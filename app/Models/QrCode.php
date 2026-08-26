<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class QrCode extends Model
{
    protected $fillable = [
        'owner_id',
        'created_by',
        'type',
        'qrable_type',
        'qrable_id',
        'token',
        'name',
        'metadata',
        'is_active',
        'scan_count',
        'last_scanned_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'scan_count' => 'integer',
        'last_scanned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (QrCode $qrCode): void {
            if (blank($qrCode->token)) {
                $qrCode->token = static::generateUniqueToken();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function qrable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (
            static::query()
                ->where('token', $token)
                ->exists()
        );

        return $token;
    }

    public function recordScan(): void
    {
        static::query()
            ->whereKey($this->getKey())
            ->update([
                'scan_count' => $this->scan_count + 1,
                'last_scanned_at' => now(),
            ]);

        $this->scan_count++;
        $this->last_scanned_at = now();
    }
}
