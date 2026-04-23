<?php

namespace App\Console\Commands;

use App\Mail\DailyStatusMail;
use App\Models\User;
use App\Services\UserStatusSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyStatusEmails extends Command
{
    protected $signature = 'emails:send-daily-status {--user_id=}';

    protected $description = 'Šalje dnevni status e-mail korisnicima kojima je ta opcija uključena';

    public function handle(UserStatusSummaryService $service): int
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('daily_status_email_enabled', true)
            ->whereNotNull('email');

        if ($this->option('user_id')) {
            $query->where('id', $this->option('user_id'));
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('Nema korisnika za slanje dnevnog status e-maila.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            try {
                $data = $service->getDailySummary($user);

                Mail::to($user->email)->send(new DailyStatusMail($data));

                $this->info("Dnevni status poslan korisniku: {$user->email}");
            } catch (\Throwable $e) {
                $this->error("Greška za {$user->email}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}