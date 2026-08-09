<?php

namespace App\Filament\Resources\Concerns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

trait HasUserTableColumn
{
    /**
     * Standardni stupac korisnika za Resource tablice.
     *
     * Stupac je:
     * - vidljiv superadminu
     * - vidljiv glavnom korisniku ako može upravljati podkorisnicima
     * - standardno skriven običnom podkorisniku
     */
    public static function userTableColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Korisnik')
            ->placeholder('-')
            ->searchable()
            ->sortable()
            ->toggleable(
                isToggledHiddenByDefault:
                    ! Auth::user()?->isSuperAdmin()
                    && ! Auth::user()?->canCreateSubusers()
            );
    }
}