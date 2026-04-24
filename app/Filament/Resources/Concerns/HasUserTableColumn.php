<?php

namespace App\Filament\Resources\Concerns;

use Filament\Tables\Columns\TextColumn;

trait HasUserTableColumn
{
    protected static function userTableColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Korisnik')
            ->badge()
            ->color('info')
            ->searchable(query: function ($query, string $search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            })
            ->sortable()
            ->toggleable()
            ->visible(fn () => static::isSuperAdmin());
    }
}