<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('parentUser.name')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('organization_name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                IconEntry::make('is_admin')
                    ->boolean(),
                TextEntry::make('role'),
                IconEntry::make('can_manage_subusers')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                IconEntry::make('daily_status_email_enabled')
                    ->boolean(),
                IconEntry::make('weekly_status_email_enabled')
                    ->boolean(),
            ]);
    }
}
