<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Osobni podaci')
                    ->schema([
                        $this->getNameFormComponent()
                            ->label('Ime'),

                        $this->getEmailFormComponent()
                            ->label('Adresa e-pošte'),

                        $this->getPasswordFormComponent()
                            ->label('Nova lozinka')
                            ->helperText(
                                'Ostavite prazno ako ne želite promijeniti lozinku.'
                            )
                            ->live(),

                        $this->getPasswordConfirmationFormComponent()
                            ->label('Potvrdi novu lozinku')
                            ->visible(
                                fn (Get $get): bool =>
                                    filled($get('password'))
                            ),
                    ])
                    ->columns(2),

                Section::make('E-mail izvještaji')
                    ->description(
                        'Odaberite koje redovne izvještaje želite primati na e-mail.'
                    )
                    ->schema([
                        Toggle::make(
                            'daily_status_email_enabled'
                        )
                            ->label('Dnevni izvještaj')
                            ->helperText(
                                'Prima dnevni pregled stanja sustava.'
                            ),

                        Toggle::make(
                            'weekly_status_email_enabled'
                        )
                            ->label('Tjedni izvještaj')
                            ->helperText(
                                'Prima tjedni pregled aktivnosti i stanja sustava.'
                            ),
                    ])
                    ->columns(2),

                Section::make('Podsjetnici o rokovima')
                    ->description(
                        'Odaberite kada želite primati e-mail podsjetnike za rokove.'
                    )
                    ->schema([
                        Toggle::make(
                            'reminder_30_days_enabled'
                        )
                            ->label(
                                '30 dana prije isteka'
                            )
                            ->default(true),

                        Toggle::make(
                            'reminder_14_days_enabled'
                        )
                            ->label(
                                '14 dana prije isteka'
                            )
                            ->default(false),

                        Toggle::make(
                            'reminder_7_days_enabled'
                        )
                            ->label(
                                '7 dana prije isteka'
                            )
                            ->default(false),

                        Toggle::make(
                            'reminder_overdue_enabled'
                        )
                            ->label(
                                'Nakon isteka'
                            )
                            ->default(true)
                            ->helperText(
                                'Podsjetnik se šalje prvi dan nakon isteka.'
                            ),
                    ])
                    ->columns(2),

                Section::make('Sigurnost')
                    ->description(
                        'Dodatno zaštitite svoj korisnički račun.'
                    )
                    ->schema([
                        Toggle::make(
                            'email_2fa_enabled'
                        )
                            ->label(
                                'Dvofaktorska autentifikacija (2FA)'
                            )
                            ->helperText(
                                fn (): string =>
                                    auth()->user()?->isSuperAdmin()
                                        ? '2FA je obavezan za Super admin račun.'
                                        : 'Kod za potvrdu prijave šalje se na vašu adresu e-pošte.'
                            )
                            ->disabled(
                                fn (): bool =>
                                    auth()->user()?->isSuperAdmin()
                                    === true
                            ),
                    ]),
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