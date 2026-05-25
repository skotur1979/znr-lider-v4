<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeoutMinutes = (int) config('security.admin_timeout_minutes', 30);

        if (Auth::check()) {
            $user = Auth::user();

            if (
                $user->last_activity_at &&
                now()->diffInMinutes($user->last_activity_at) >= $timeoutMinutes
            ) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/admin/login')
                    ->with('status', 'Sesija je istekla zbog neaktivnosti. Prijavite se ponovno.');
            }

            $user->forceFill([
                'last_activity_at' => now(),
            ])->save();
        }

        return $next($request);
    }
}