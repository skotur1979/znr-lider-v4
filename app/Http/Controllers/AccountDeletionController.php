<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    public function requestDeletion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'account_deletion_requested_at' => now(),
            'gdpr_request_status' => 'requested',
            'account_deletion_reason' => $data['reason'] ?? null,
        ])->save();

        ActivityLogger::log(
            module: 'GDPR',
            action: 'account_deletion_requested',
            title: 'Zahtjev za brisanje korisničkog računa',
            description: 'Korisnik je podnio zahtjev za brisanje korisničkog računa.',
            record: $user,
        );

        return back()->with(
            'status',
            'Zahtjev za brisanje korisničkog računa je evidentiran. Administrator će obraditi zahtjev u skladu s rokovima čuvanja i zakonskim obvezama.'
        );
    }
}
