<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Resetiranje lozinke - ZNR LIDER')
            ->greeting('Poštovani,')
            ->line('Zaprimljen je zahtjev za resetiranje lozinke vašeg korisničkog računa u sustavu ZNR LIDER.')
            ->line('Ako ste vi zatražili promjenu lozinke, kliknite na gumb ispod.')
            ->action('Resetiraj lozinku', $url)
            ->line('Poveznica za resetiranje lozinke vrijedi 60 minuta.')
            ->line('Ako niste zatražili promjenu lozinke, nije potrebno poduzimati nikakve radnje.')
            ->salutation('Srdačan pozdrav,' . PHP_EOL . 'ZNR LIDER');
    }
}