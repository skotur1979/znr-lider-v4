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
            'newsletter_opt_in' => ['nullable', 'boolean'],
        ], [
            'accepted_terms.accepted' => 'Potrebno je prihvatiti Uvjete korištenja.',
            'accepted_privacy.accepted' => 'Potrebno je prihvatiti Pravila privatnosti.',
        ]);

        $user = $request->user();

        $acceptedAt = now();
        $termsVersion = config('legal.terms_version');
        $privacyVersion = config('legal.privacy_version');
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
            'newsletter_opt_in' => $newsletter,
            'accepted_at' => $acceptedAt,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        ActivityLogger::log(
            module: 'GDPR',
            action: 'legal_acceptance',
            title: 'Prihvaćeni uvjeti korištenja i pravila privatnosti',
            description: 'Korisnik je prihvatio uvjete verzija ' . $termsVersion . ' i pravila privatnosti verzija ' . $privacyVersion . '.',
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