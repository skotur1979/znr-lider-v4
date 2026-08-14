<?php

namespace App\Http\Controllers;

use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

class TestAttemptController
{
    public function show(TestAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        $attempt->load([
            'test.questions.answers',
            'odgovori',
            'user',
        ]);

        return view(
            'test-result.show',
            compact('attempt')
        );
    }

    public function downloadPdf(TestAttempt $attempt)
    {
        $this->authorizeAttempt($attempt);

        $attempt->load([
            'test.questions.answers',
            'odgovori',
            'user',
        ]);

        $html = view(
            'test-result.pdf',
            compact('attempt')
        )->render();

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/temp'),
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output('', 'S'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="test-attempt-'
                    . $attempt->id
                    . '.pdf"',
            ]
        );
    }

    private function authorizeAttempt(TestAttempt $attempt): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        /*
         * Superadmin smije pregledavati sve rezultate testiranja.
         */
        if ($user->isSuperAdmin()) {
            return;
        }

        /*
         * Glavni korisnik i podkorisnici iste organizacije
         * koriste isti ownerId().
         */
        abort_unless(
            (int) $attempt->user_id ===
                (int) $user->ownerId(),
            403
        );
    }
}