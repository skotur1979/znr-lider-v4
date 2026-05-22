<?php

namespace App\Filament\Resources\LegalAcceptances\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class LegalAcceptancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('accepted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Prihvaćeno')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Korisnik')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organizacija')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('terms_version')
                    ->label('Uvjeti')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('privacy_version')
                    ->label('Privatnost')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('newsletter_opt_in')
                    ->label('Newsletter')
                    ->boolean(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User agent')
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('Prikaži'),
            ])
            ->toolbarActions([]);
    }
}