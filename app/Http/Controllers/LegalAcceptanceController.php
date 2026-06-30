<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use Illuminate\Http\Request;

class LegalAcceptanceController extends Controller
{
    public function show()
    {
        return view('legal.accept', [
            'termsVersion' => config('legal.terms_version'),
            'privacyVersion' => config('legal.privacy_version'),
            'cookiesVersion' => config('legal.cookies_version'),
            'dpaVersion' => config('legal.dpa_version'),
            'securityVersion' => config('legal.security_version'),
            'retentionVersion' => config('legal.retention_version'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'accepted_terms' => ['required', 'accepted'],
            'accepted_privacy' => ['required', 'accepted'],
            'accepted_cookies' => ['required', 'accepted'],
            'accepted_dpa' => ['required', 'accepted'],
            'accepted_security' => ['required', 'accepted'],
            'accepted_retention' => ['required', 'accepted'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $now = now();

        $user->forceFill([
            'accepted_terms_at' => $now,
            'accepted_privacy_at' => $now,
            'cookies_accepted_at' => $now,
            'dpa_accepted_at' => $now,
            'security_accepted_at' => $now,
            'retention_accepted_at' => $now,

            'terms_version' => config('legal.terms_version'),
            'privacy_version' => config('legal.privacy_version'),
            'cookies_version' => config('legal.cookies_version'),
            'dpa_version' => config('legal.dpa_version'),
            'security_version' => config('legal.security_version'),
            'retention_version' => config('legal.retention_version'),

            'newsletter_opt_in' => (bool) ($data['newsletter_opt_in'] ?? false),
            'legal_consent_withdrawn_at' => null,
            'legal_consent_withdrawn_reason' => null,
        ])->save();

        LegalAcceptance::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'organization_name' => $user->organization_name,

            'terms_version' => config('legal.terms_version'),
            'privacy_version' => config('legal.privacy_version'),
            'cookies_version' => config('legal.cookies_version'),
            'dpa_version' => config('legal.dpa_version'),
            'security_version' => config('legal.security_version'),
            'retention_version' => config('legal.retention_version'),

            'accepted_documents' => [
                'terms',
                'privacy',
                'cookies',
                'dpa',
                'security',
                'retention',
            ],

            'newsletter_opt_in' => (bool) ($data['newsletter_opt_in'] ?? false),
            'accepted_at' => $now,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect('/admin');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->forceFill([
            'legal_consent_withdrawn_at' => now(),
            'legal_consent_withdrawn_reason' => $data['reason'] ?? null,
        ])->save();

        return back()->with('status', 'Privola je povučena.');
    }
}