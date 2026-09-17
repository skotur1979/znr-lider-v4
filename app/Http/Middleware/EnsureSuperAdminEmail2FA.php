<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminEmail2FA
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user =
            Filament::auth()->user()
            ?? $request->user();

        if (! $user) {
            return $next($request);
        }

        $requiresTwoFactor =
            (
                method_exists(
                    $user,
                    'isSuperAdmin'
                )
                && $user->isSuperAdmin()
            )
            || (bool) $user->email_2fa_enabled;

        if (! $requiresTwoFactor) {
            return $next($request);
        }

        if (
            $request->routeIs(
                'email-2fa.*'
            )
        ) {
            return $next($request);
        }

        /*
         * Potvrda 2FA vezana je uz konkretan
         * korisnički račun.
         */
        if (
            (int) session()->get(
                'email_2fa_user_id'
            )
            !== (int) $user->id
        ) {
            return redirect()
                ->route(
                    'email-2fa.verify'
                );
        }

        return $next($request);
    }
}