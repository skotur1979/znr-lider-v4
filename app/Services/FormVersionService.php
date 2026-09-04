<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FormVersionService
{
    public const RA1_CURRENT = 'RA1_2026';
    public const NR1_CURRENT = 'NR1_2026';
    public const WORK_PERMIT_CURRENT = 'WORK_PERMIT_2026';
    public const OZO_CURRENT = 'OZO_2026';

    /*
    |--------------------------------------------------------------------------
    | PL-O verzije
    |--------------------------------------------------------------------------
    */

    public const PLO_OLD = 'PLO_2026';
    public const PLO_CURRENT = 'PLO_2026_09';

    /*
     * Nova verzija službeno se koristi od 08.09.2026.
     */
    public const PLO_NEW_VALID_FROM = '2026-09-08';

    public static function ploVersions(): array
    {
        return [
            self::PLO_OLD =>
                'PL-O obrazac 2026 / stara verzija',

            self::PLO_CURRENT =>
                'PL-O obrazac 2026 / od 08.09.2026. / trenutno važeći',
        ];
    }

    public static function currentPlo(): string
    {
        return self::PLO_CURRENT;
    }

    /**
     * Preporučena verzija prema datumu predaje.
     *
     * Ovo NE zabranjuje korisniku ručni odabir druge verzije.
     */
    public static function ploForDate(
        CarbonInterface|string|null $date
    ): string {
        if (blank($date)) {
            return self::currentPlo();
        }

        $date = $date instanceof CarbonInterface
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        $validFrom = Carbon::parse(
            self::PLO_NEW_VALID_FROM
        )->startOfDay();

        return $date->lt($validFrom)
            ? self::PLO_OLD
            : self::PLO_CURRENT;
    }

    public static function isOldPlo(
        ?string $version
    ): bool {
        return $version === self::PLO_OLD;
    }

    public static function isCurrentPlo(
        ?string $version
    ): bool {
        return $version === self::PLO_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | RA-1
    |--------------------------------------------------------------------------
    */

    public static function ra1Versions(): array
    {
        return [
            self::RA1_CURRENT =>
                'RA-1 obrazac 2026 / trenutno važeći',
        ];
    }

    public static function currentRa1(): string
    {
        return self::RA1_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | NR-1
    |--------------------------------------------------------------------------
    */

    public static function nr1Versions(): array
    {
        return [
            self::NR1_CURRENT =>
                'NR-1 obrazac 2026 / trenutno važeći',
        ];
    }

    public static function currentNr1(): string
    {
        return self::NR1_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | Dozvole za rad
    |--------------------------------------------------------------------------
    */

    public static function workPermitVersions(): array
    {
        return [
            self::WORK_PERMIT_CURRENT =>
                'Dozvola za rad 2026 / trenutno važeći obrazac',
        ];
    }

    public static function currentWorkPermit(): string
    {
        return self::WORK_PERMIT_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | OZO
    |--------------------------------------------------------------------------
    */

    public static function ozoVersions(): array
    {
        return [
            self::OZO_CURRENT =>
                'Obrazac OZO 2026 / trenutno važeći',
        ];
    }

    public static function currentOzo(): string
    {
        return self::OZO_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | Putanja do PDF predložaka
    |--------------------------------------------------------------------------
    */

    public static function templatePath(
        string $formKey,
        string $version
    ): string {
        return resource_path(
            "templates/{$formKey}/{$version}.pdf"
        );
    }
}