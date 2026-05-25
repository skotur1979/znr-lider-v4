<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPrivacyController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'export_generated_at' => now()->toDateTimeString(),

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'organization_name' => $user->organization_name,
                'email' => $user->email,
                'role' => $user->role,
                'parent_user_id' => $user->parent_user_id,
                'is_active' => $user->is_active,
                'created_at' => optional($user->created_at)->toDateTimeString(),
                'updated_at' => optional($user->updated_at)->toDateTimeString(),
            ],

            'legal' => [
                'accepted_terms_at' => optional($user->accepted_terms_at)->toDateTimeString(),
                'accepted_privacy_at' => optional($user->accepted_privacy_at)->toDateTimeString(),
                'terms_version' => $user->terms_version,
                'privacy_version' => $user->privacy_version,
                'newsletter_opt_in' => $user->newsletter_opt_in,
                'legal_consent_withdrawn_at' => optional($user->legal_consent_withdrawn_at)->toDateTimeString(),
                'legal_consent_withdrawn_reason' => $user->legal_consent_withdrawn_reason,
                'account_deletion_requested_at' => optional($user->account_deletion_requested_at)->toDateTimeString(),
                'account_deletion_reason' => $user->account_deletion_reason,
            ],

            'module_access' => $user->moduleAccess(),

            'legal_acceptance_history' => $user->legalAcceptances()
                ->latest('accepted_at')
                ->get()
                ->map(fn ($item) => [
                    'terms_version' => $item->terms_version,
                    'privacy_version' => $item->privacy_version,
                    'newsletter_opt_in' => $item->newsletter_opt_in,
                    'accepted_at' => optional($item->accepted_at)->toDateTimeString(),
                    'ip_address' => $item->ip_address,
                    'user_agent' => $item->user_agent,
                ])
                ->values(),
        ];

        ActivityLogger::log(
            module: 'GDPR',
            action: 'personal_data_export',
            title: 'Export osobnih podataka korisnika',
            description: 'Korisnik je preuzeo export svojih osobnih podataka.',
            record: $user,
        );

        return response()
            ->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="moji-osobni-podaci-' . $user->id . '.json"');
    }
}
