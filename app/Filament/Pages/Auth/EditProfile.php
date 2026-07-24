<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
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