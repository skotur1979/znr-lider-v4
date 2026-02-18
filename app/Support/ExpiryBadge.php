<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

final class ExpiryBadge
{
    public static function color(mixed $state, int $daysSoon = 30): string
    {
        $date = self::toCarbon($state);

        if (! $date) return 'gray';

        $today = Carbon::today();

        if ($date->lt($today)) return 'danger';
        if ($date->lte($today->copy()->addDays($daysSoon))) return 'warning';

        return 'success';
    }

    public static function classes(mixed $state, int $daysSoon = 30): string
    {
        $date = self::toCarbon($state);

        if (! $date) {
            return 'font-semibold ring-2 ring-inset ring-gray-500/70 bg-gray-500/15 text-gray-200';
        }

        $today = Carbon::today();

        if ($date->lt($today)) {
            return 'font-semibold ring-2 ring-inset ring-red-500 bg-red-600/30 text-red-100';
        }

        if ($date->lte($today->copy()->addDays($daysSoon))) {
            return 'font-semibold ring-2 ring-inset ring-amber-400 bg-amber-500/30 text-amber-100';
        }

        return 'font-semibold ring-2 ring-inset ring-green-500 bg-green-600/30 text-green-100';
    }

    public static function icon(mixed $state, int $daysSoon = 30): ?string
    {
        $date = self::toCarbon($state);

        if (! $date) return null;

        $today = Carbon::today();

        if ($date->lt($today)) return 'heroicon-o-x-circle';
        if ($date->lte($today->copy()->addDays($daysSoon))) return 'heroicon-o-exclamation-triangle';

        return 'heroicon-o-check-circle';
    }

    public static function tooltip(mixed $state, int $daysSoon = 30): string
    {
        $date = self::toCarbon($state);

        if (! $date) return 'Rok nije definiran';

        $today = Carbon::today();

        if ($date->lt($today)) return 'Rok je istekao';
        if ($date->lte($today->copy()->addDays($daysSoon))) return "Rok ističe unutar {$daysSoon} dana";

        return 'Rok je važeći';
    }

    // backward compatible
    public static function expiryColor(mixed $state, int $daysSoon = 30): string { return self::color($state, $daysSoon); }
    public static function expiryClasses(mixed $state, int $daysSoon = 30): string { return self::classes($state, $daysSoon); }
    public static function expiryIcon(mixed $state, int $daysSoon = 30): ?string { return self::icon($state, $daysSoon); }
    public static function expiryTooltip(mixed $state, int $daysSoon = 30): string { return self::tooltip($state, $daysSoon); }

    private static function toCarbon(mixed $value): ?Carbon
    {
        if (blank($value)) return null;

        if ($value instanceof Carbon) return $value->copy();
        if ($value instanceof DateTimeInterface) return Carbon::instance($value);

        if (is_string($value)) {
            try { return Carbon::parse($value); } catch (\Throwable) { return null; }
        }

        return null;
    }
}

