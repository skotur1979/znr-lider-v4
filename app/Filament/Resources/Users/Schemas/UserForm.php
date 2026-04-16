<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_user_id')
                    ->relationship('parentUser', 'name')
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                TextInput::make('organization_name')
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('role')
                    ->required()
                    ->default('korisnik'),
                Textarea::make('quick_actions')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('can_manage_subusers')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('daily_status_email_enabled')
                    ->required(),
                Toggle::make('weekly_status_email_enabled')
                    ->required(),
            ]);
    }
}
