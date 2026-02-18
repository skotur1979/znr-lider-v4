<?php

namespace App\Support;

use Illuminate\Support\Carbon;

trait ExpiryBadge
{
    public static function expiryColor($state): string
    {
        if (blank($state)) {
            return 'gray';
        }

        $date  = Carbon::parse($state);
        $today = Carbon::today();

        if ($date->lt($today)) {
            return 'danger'; // isteklo
        }

        if ($date->lte($today->copy()->addDays(30))) {
            return 'warning'; // ističe u 30 dana
        }

        return 'success'; // > 30 dana
    }

    public static function expiryClasses($state): string
    {
        if (blank($state)) {
            return 'font-semibold ring-2 ring-inset ring-gray-500/70 bg-gray-500/15 text-gray-200';
        }

        $date  = Carbon::parse($state);
        $today = Carbon::today();

        if ($date->lt($today)) {
            return 'font-semibold ring-2 ring-inset ring-red-500 bg-red-600/30 text-red-100';
        }

        if ($date->lte($today->copy()->addDays(30))) {
            return 'font-semibold ring-2 ring-inset ring-amber-400 bg-amber-500/30 text-amber-100';
        }

        return 'font-semibold ring-2 ring-inset ring-green-500 bg-green-600/30 text-green-100';
    }

    public static function expiryIcon($state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date  = Carbon::parse($state);
        $today = Carbon::today();

        if ($date->lt($today)) {
            return 'heroicon-o-x-circle';
        }

        if ($date->lte($today->copy()->addDays(30))) {
            return 'heroicon-o-exclamation-triangle';
        }

        return 'heroicon-o-check-circle';
    }

    public static function expiryTooltip($state): string
    {
        if (blank($state)) {
            return 'Rok nije definiran';
        }

        $date  = Carbon::parse($state);
        $today = Carbon::today();

        if ($date->lt($today)) {
            return 'Rok je istekao';
        }

        if ($date->lte($today->copy()->addDays(30))) {
            return 'Rok ističe unutar 30 dana';
        }

        return 'Rok je važeći';
    }
}
