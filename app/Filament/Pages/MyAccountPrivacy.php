<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class MyAccountPrivacy extends Page
{
    protected string $view = 'filament.pages.my-account-privacy';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Moj račun i privatnost';

    protected static ?string $title = 'Moj račun i privatnost';

    protected static ?string $slug = 'moj-racun-privatnost';

    protected static string|\UnitEnum|null $navigationGroup = 'Korisnički račun';

    protected static ?int $navigationSort = 999;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check();
    }

    public function getUserProperty()
    {
        return Auth::user();
    }
}
