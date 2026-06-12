<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalAcceptanceController extends Controller
{
    public function show(Request $request): View
    {
        return view('legal.accept', [
            'user' => $request->user(),
            'termsVersion' => config('legal.terms_version'),
            'privacyVersion' => config('legal.privacy_version'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'accepted_terms' => ['accepted'],
            'accepted_privacy' => ['accepted'],
            'accepted_cookies' => ['accepted'],
            'accepted_dpa' => ['accepted'],
            'accepted_security' => ['accepted'],
            'accepted_retention' => ['accepted'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
        ], [
            'accepted_terms.accepted' => 'Potrebno je prihvatiti Opće uvjete korištenja.',
            'accepted_privacy.accepted' => 'Potrebno je prihvatiti Pravila privatnosti.',
            'accepted_cookies.accepted' => 'Potrebno je potvrditi Politiku kolačića.',
            'accepted_dpa.accepted' => 'Potrebno je prihvatiti Ugovor o obradi podataka.',
            'accepted_security.accepted' => 'Potrebno je potvrditi Politiku sigurnosti.',
            'accepted_retention.accepted' => 'Potrebno je potvrditi Politiku zadržavanja i brisanja podataka.',
        ]);

        $user = $request->user();

        $acceptedAt = now();

        $termsVersion = config('legal.terms_version');
        $privacyVersion = config('legal.privacy_version');
        $cookiesVersion = config('legal.cookies_version');
        $dpaVersion = config('legal.dpa_version');
        $securityVersion = config('legal.security_version');
        $retentionVersion = config('legal.retention_version');

        $newsletter = (bool) ($data['newsletter_opt_in'] ?? false);

        $user->forceFill([
            'accepted_terms_at' => $acceptedAt,
            'accepted_privacy_at' => $acceptedAt,
            'terms_version' => $termsVersion,
            'privacy_version' => $privacyVersion,
            'newsletter_opt_in' => $newsletter,
            'legal_consent_withdrawn_at' => null,
            'legal_consent_withdrawn_reason' => null,
        ])->save();

        LegalAcceptance::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'organization_name' => $user->organization_name ?: $user->owner()?->organization_name,

            'terms_version' => $termsVersion,
            'privacy_version' => $privacyVersion,
            'cookies_version' => $cookiesVersion,
            'dpa_version' => $dpaVersion,
            'security_version' => $securityVersion,
            'retention_version' => $retentionVersion,

            'accepted_documents' => [
                'terms' => $termsVersion,
                'privacy' => $privacyVersion,
                'cookies' => $cookiesVersion,
                'dpa' => $dpaVersion,
                'security' => $securityVersion,
                'retention' => $retentionVersion,
                'accepted_at' => $acceptedAt->toDateTimeString(),
            ],

            'newsletter_opt_in' => $newsletter,
            'accepted_at' => $acceptedAt,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        ActivityLogger::log(
            module: 'GDPR',
            action: 'legal_acceptance',
            title: 'Prihvaćeni pravni dokumenti',
            description: 'Korisnik je prihvatio pravne dokumente: uvjeti ' . $termsVersion .
                ', privatnost ' . $privacyVersion .
                ', kolačići ' . $cookiesVersion .
                ', DPA ' . $dpaVersion .
                ', sigurnost ' . $securityVersion .
                ', zadržavanje podataka ' . $retentionVersion . '.',
            record: $user,
        );

        return redirect()->intended('/admin');
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'accepted_terms_at' => null,
            'accepted_privacy_at' => null,
            'terms_version' => null,
            'privacy_version' => null,
            'newsletter_opt_in' => false,
            'legal_consent_withdrawn_at' => now(),
            'legal_consent_withdrawn_reason' => $data['reason'] ?? null,
        ])->save();

        ActivityLogger::log(
            module: 'GDPR',
            action: 'legal_consent_withdrawn',
            title: 'Povučena privola / prihvaćanje pravnih uvjeta',
            description: 'Korisnik je povukao prihvaćanje pravnih uvjeta. Za nastavak korištenja sustava bit će potrebno ponovno prihvaćanje važeće verzije dokumenata.',
            record: $user,
        );

        return redirect()
            ->route('legal.accept')
            ->with('status', 'Privola je povučena. Za nastavak korištenja sustava potrebno je ponovno prihvatiti važeće dokumente.');
    }
}