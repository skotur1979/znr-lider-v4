<?php

namespace App\Http\Controllers;

use App\Models\TestAttempt;
use Mpdf\Mpdf;

class TestAttemptController
{
    public function show(TestAttempt $attempt)
    {
        abort_unless(
            auth()->user()?->isAdmin() || (int) $attempt->user_id === (int) auth()->id(),
            403
        );

        $attempt->load([
            'test.questions.answers',
            'odgovori',
            'user',
        ]);

        return view('test-result.show', compact('attempt'));
    }

    public function downloadPdf(TestAttempt $attempt)
{
    abort_unless(auth()->user()?->isAdmin() || (int) $attempt->user_id === (int) auth()->id(), 403);

    $attempt->load(['test.questions.answers', 'odgovori', 'user']);

    $html = view('test-result.pdf', compact('attempt'))->render();

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

    return response($mpdf->Output('', 'S'), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="test-attempt-'.$attempt->id.'.pdf"',
    ]);
}
}