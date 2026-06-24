<?php

namespace App\Providers;

use App\Filament\Widgets\DashboardCalendarWidget;
use App\Filament\Widgets\DashboardDeadlinesGrid;
use App\Models\ActivityLog;
use App\Models\OperationalLog;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $expireMinutes = (int) config('auth.passwords.users.expire', 60);

            $url = URL::temporarySignedRoute(
                'filament.admin.auth.password-reset.reset',
                now()->addMinutes($expireMinutes),
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ],
            );

            return (new MailMessage)
                ->subject('Resetiranje lozinke - ZNR LIDER')
                ->greeting('Poštovani,')
                ->line('Zaprimljen je zahtjev za resetiranje lozinke vašeg korisničkog računa u sustavu ZNR LIDER.')
                ->line('Ako ste vi zatražili promjenu lozinke, kliknite na gumb ispod.')
                ->action('Resetiraj lozinku', $url)
                ->line("Poveznica za resetiranje lozinke vrijedi {$expireMinutes} minuta.")
                ->line('Ako niste zatražili promjenu lozinke, nije potrebno poduzimati nikakve radnje.')
                ->line('Ako gumb ne radi, kopirajte i otvorite sljedeću poveznicu u internetskom pregledniku:')
                ->line($url)
                ->salutation('Srdačan pozdrav,' . PHP_EOL . 'ZNR LIDER');
        });

        Livewire::component(
            'app.filament.widgets.dashboard-deadlines-grid',
            DashboardDeadlinesGrid::class
        );

        Livewire::component(
            'app.filament.widgets.dashboard-calendar-widget',
            DashboardCalendarWidget::class
        );

        Event::listen(Login::class, function (Login $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'login',
                title: 'Korisnik se prijavio',
                description: 'Korisnik: ' . ($event->user?->name ?? $event->user?->email ?? '-'),
                record: $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'logout',
                title: 'Korisnik se odjavio',
                description: 'Korisnik: ' . ($event->user?->name ?? $event->user?->email ?? '-'),
                record: $event->user,
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'failed_login',
                title: 'Neuspješna prijava',
                description: 'Pokušaj prijave za e-mail: ' . ($event->credentials['email'] ?? '-'),
            );
        });

        Event::listen('eloquent.saving: *', function (string $eventName, array $data) {
            $model = $data[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            if ($model instanceof ActivityLog) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            if (! Schema::hasColumn($model->getTable(), 'user_id')) {
                return;
            }

            $user = Auth::user();

            if ($model instanceof OperationalLog) {
                if (! $user?->isSuperAdmin()) {
                    $model->user_id = $user->id;
                } elseif (! $model->exists && empty($model->user_id)) {
                    $model->user_id = $user->id;
                }

                return;
            }

            if ($user?->isSuperAdmin()) {
                if (! $model->exists && empty($model->user_id)) {
                    $model->user_id = $user->id;
                }

                return;
            }

            $model->user_id = $user->ownerId();
        });
    }
}