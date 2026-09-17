<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(
        array $data
    ) {
        $this->data = $data;
    }

    public function build(): self
    {
        return $this
            ->subject(
                'ZNR LIDER - '
                . $this->data['label']
            )
            ->view(
                'emails.deadline-reminder'
            );
    }
}