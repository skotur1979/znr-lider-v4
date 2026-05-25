<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminEmail2FA
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! method_exists($user, 'isSuperAdmin') || ! $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('email-2fa.*')) {
            return $next($request);
        }

        if (! session()->get('superadmin_email_2fa_passed')) {
            return redirect()->route('email-2fa.verify');
        }

        return $next($request);
    }
}