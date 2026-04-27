<?php

namespace App\Mail;

use App\Models\Observation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ObservationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $imagePath = null;

    public function __construct(
        public Observation $observation,
        public string $mode = 'created',
        public array $oldData = [],
    ) {
        if ($this->observation->picture_path) {
            $path = storage_path('app/public/' . $this->observation->picture_path);

            if (file_exists($path)) {
                $this->imagePath = $path;
            }
        }
    }

    public function build()
    {
        $subject = $this->mode === 'updated'
            ? 'ZNR LIDER - Izmjena zapažanja'
            : 'ZNR LIDER - Novo zapažanje';

        return $this
            ->subject($subject)
            ->view('emails.observation-notification');
    }
}