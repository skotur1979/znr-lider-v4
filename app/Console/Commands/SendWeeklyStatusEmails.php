<?php

namespace App\Console\Commands;

use App\Mail\WeeklyStatusMail;
use App\Models\User;
use App\Services\UserStatusSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyStatusEmails extends Command
{
    protected $signature = 'emails:send-weekly-status {--user_id=}';

    protected $description = 'Šalje tjedni status e-mail korisnicima kojima je ta opcija uključena';

    public function handle(UserStatusSummaryService $service): int
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('weekly_status_email_enabled', true)
            ->whereNotNull('email');

        if ($this->option('user_id')) {
            $query->where('id', $this->option('user_id'));
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('Nema korisnika za slanje tjednog status e-maila.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            try {
                $data = $service->getWeeklySummary($user);

                Mail::to($user->email)->send(new WeeklyStatusMail($data));

                $this->info("Tjedni status poslan korisniku: {$user->email}");
            } catch (\Throwable $e) {
                $this->error("Greška za {$user->email}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}