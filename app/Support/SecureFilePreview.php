<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class SecureFilePreview
{
    /**
     * Generira privremeni potpisani URL za pregled datoteke.
     *
     * Organizacijski korisnici:
     * - URL je vezan uz ownerId() njihove organizacije.
     *
     * Superadmin:
     * - owner = 0
     * - pristup se provjerava kao superadmin na samoj ruti.
     */
    public static function url(
        ?string $file,
        int $minutes = 30
    ): ?string {
        if (blank($file)) {
            return null;
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $file = self::normalizePath($file);

        if ($file === '') {
            return null;
        }

        $ownerId = $user->isSuperAdmin()
            ? 0
            : (int) $user->ownerId();

        if (! $user->isSuperAdmin() && $ownerId <= 0) {
            return null;
        }

        return URL::temporarySignedRoute(
            'file.preview',
            now()->addMinutes($minutes),
            [
                'file' => $file,
                'owner' => $ownerId,
            ]
        );
    }

    /**
     * Normalizacija relativne putanje.
     */
    public static function normalizePath(
        string $file
    ): string {
        $file = str_replace('\\', '/', $file);

        return ltrim(
            trim($file),
            '/'
        );
    }
}