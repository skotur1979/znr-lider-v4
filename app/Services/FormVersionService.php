<?php

namespace App\Services;

class FormVersionService
{
    public const RA1_CURRENT = 'RA1_2026';
    public const NR1_CURRENT = 'NR1_2026';
    public const WORK_PERMIT_CURRENT = 'WORK_PERMIT_2026';
    public const OZO_CURRENT = 'OZO_2026';
    public const PLO_CURRENT = 'PLO_2026';

    public static function ploVersions(): array
    {
        return [
            self::PLO_CURRENT => 'PL-O obrazac 2026 / trenutno važeći',
        ];
    }

    public static function currentPlo(): string
    {
        return self::PLO_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | RA-1
    |--------------------------------------------------------------------------
    */

    public static function ra1Versions(): array
    {
        return [
            self::RA1_CURRENT => 'RA-1 obrazac 2026 / trenutno važeći',
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
            self::NR1_CURRENT => 'NR-1 obrazac 2026 / trenutno važeći',
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
            self::WORK_PERMIT_CURRENT => 'Dozvola za rad 2026 / trenutno važeći obrazac',
        ];
    }

    public static function currentWorkPermit(): string
    {
        return self::WORK_PERMIT_CURRENT;
    }

    /*
    |--------------------------------------------------------------------------
    | OZO (spremno za budućnost)
    |--------------------------------------------------------------------------
    */

    public static function ozoVersions(): array
    {
        return [
            self::OZO_CURRENT => 'Obrazac OZO 2026 / trenutno važeći',
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

    public static function templatePath(string $formKey, string $version): string
    {
        return resource_path("templates/{$formKey}/{$version}.pdf");
    }
}