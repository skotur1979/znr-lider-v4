<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent()
                    ->label('Ime'),

                $this->getEmailFormComponent()
                    ->label('Adresa e-pošte'),

                $this->getPasswordFormComponent()
                    ->label('Nova lozinka')
                    ->helperText('Ostavite prazno ako ne želite promijeniti lozinku.')
                    ->live(),

                $this->getPasswordConfirmationFormComponent()
                    ->label('Potvrdi novu lozinku')
                    ->visible(
                        fn (Get $get): bool => filled($get('password'))
                    ),
            ]);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Odustani')
            ->url('/admin');
    }

    protected function getRedirectUrl(): string
    {
        return '/admin';
    }
}