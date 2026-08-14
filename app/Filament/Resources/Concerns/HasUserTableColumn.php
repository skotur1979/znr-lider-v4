<?php

namespace App\Filament\Resources\Concerns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

trait HasUserTableColumn
{
    /**
     * Standardni stupac korisnika za Resource tablice.
     *
     * Stupac je vidljiv samo superadminu.
     * Glavni korisnik i podkorisnici ga ne vide.
     */
    public static function userTableColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Korisnik')
            ->placeholder('-')
            ->searchable()
            ->sortable()
            ->visible(
                fn (): bool =>
                    Auth::user()?->isSuperAdmin() === true
            );
    }
}