<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class SystemHealth extends Page
{
    protected string $view = 'filament.pages.system-health';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Status sustava';

    protected static ?string $title = 'Status sustava';

    protected static ?string $slug = 'status-sustava';

    protected static string | UnitEnum | null $navigationGroup = 'Administracija';

    protected static ?int $navigationSort = 98;

    public array $checks = [];

    public function mount(): void
    {
        $this->loadChecks();
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->isSuperAdmin();
    }

    public function loadChecks(): void
    {
        $appUrl = (string) config('app.url');
        $backupPath = storage_path('app/private/ZNR LIDER');

        $this->checks = [
            [
                'label' => 'Okruženje',
                'value' => app()->environment(),
                'ok' => app()->environment('production'),
                'note' => app()->environment('production')
                    ? 'Produkcijsko okruženje'
                    : 'Lokalno/testno okruženje',
            ],
            [
                'label' => 'Debug mode',
                'value' => config('app.debug') ? 'UKLJUČEN' : 'ISKLJUČEN',
                'ok' => ! config('app.debug'),
                'note' => config('app.debug')
                    ? 'Prije produkcije mora biti APP_DEBUG=false'
                    : 'Ispravno za produkciju',
            ],
            [
                'label' => 'APP URL',
                'value' => $appUrl,
                'ok' => ! str_contains($appUrl, '127.0.0.1') && ! str_contains($appUrl, 'localhost'),
                'note' => str_contains($appUrl, '127.0.0.1') || str_contains($appUrl, 'localhost')
                    ? 'Prije produkcije postaviti stvarnu domenu'
                    : 'URL izgleda produkcijski',
            ],
            [
                'label' => 'Mail driver',
                'value' => (string) config('mail.default'),
                'ok' => config('mail.default') === 'smtp',
                'note' => 'Trenutni mail driver',
            ],
            [
                'label' => 'Queue driver',
                'value' => (string) config('queue.default'),
                'ok' => true,
                'note' => config('queue.default') === 'sync'
                    ? 'Sync je OK ako nemaš queue worker'
                    : 'Koristi queue worker',
            ],
            [
                'label' => 'Storage link',
                'value' => File::exists(public_path('storage')) ? 'LINKED' : 'NIJE LINKED',
                'ok' => File::exists(public_path('storage')),
                'note' => 'Provjera public/storage symbolic linka',
            ],
            [
                'label' => 'Backup folder',
                'value' => File::exists($backupPath) ? 'Postoji' : 'Nije pronađen',
                'ok' => File::exists($backupPath),
                'note' => 'Provjera foldera: storage/app/private/ZNR LIDER',
            ],
            [
                'label' => 'Backup command',
                'value' => 'backup:list',
                'ok' => true,
                'note' => 'Status backup sustava uspješno dohvaćen',
            ],
        ];
    }

    public function runBackupList(): string
    {
        Artisan::call('backup:list');

        return Artisan::output();
    }

    public function sendTestMail(): void
    {
        Mail::raw('Test mail iz ZNR LIDER aplikacije.', function ($message) {
            $message->to(Auth::user()->email)
                ->subject('ZNR LIDER - test mail');
        });

        Notification::make()
            ->title('Testni mail je poslan.')
            ->success()
            ->send();
    }
}