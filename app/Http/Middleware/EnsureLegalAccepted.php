<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegalAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasAcceptedCurrentLegalTerms()) {
            return $next($request);
        }

        if ($request->routeIs(
            'legal.accept',
            'legal.accept.store',

            'legal.privacy',
            'legal.privacy.pdf',

            'legal.terms',
            'legal.terms.pdf',

            'legal.cookies',
            'legal.dpa',
            'legal.security',
            'legal.retention',
            'legal.faq',

            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('legal.accept');
    }
}