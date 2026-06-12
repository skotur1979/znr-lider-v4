<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\TestAttemptController;
use App\Http\Controllers\ZnrGeneralReportController;
use App\Http\Controllers\LegalAcceptanceController;
use App\Http\Controllers\LegalDocumentPdfController;
use App\Http\Controllers\UserPrivacyController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\EmailTwoFactorController;

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Testovi
    |--------------------------------------------------------------------------
    */

    Route::get('/test-attempts/{attempt}', [TestAttemptController::class, 'show'])
        ->name('test-attempts.show');

    Route::get('/test-attempts/{attempt}/pdf', [TestAttemptController::class, 'downloadPdf'])
        ->name('test-attempts.download');

    /*
    |--------------------------------------------------------------------------
    | ZNR General Report
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/znr-general-report/pdf', [ZnrGeneralReportController::class, 'pdf'])
        ->name('znr.general-report.pdf');

    /*
    |--------------------------------------------------------------------------
    | EMAIL 2FA za Super Admina
    |--------------------------------------------------------------------------
    */

    Route::get('/email-2fa/verify', [EmailTwoFactorController::class, 'verify'])
        ->name('email-2fa.verify');

    Route::post('/email-2fa/confirm', [EmailTwoFactorController::class, 'confirm'])
        ->name('email-2fa.confirm');

    Route::post('/email-2fa/resend', [EmailTwoFactorController::class, 'resend'])
        ->name('email-2fa.resend');

    /*
    |--------------------------------------------------------------------------
    | GDPR / Pravni dokumenti
    |--------------------------------------------------------------------------
    */

    Route::view('/pravila-privatnosti', 'legal.privacy')
        ->name('legal.privacy');

    Route::view('/uvjeti-koristenja', 'legal.terms')
        ->name('legal.terms');

    Route::view('/politika-kolacica', 'legal.cookies')
        ->name('legal.cookies');

    Route::get('/pravila-privatnosti/pdf', [LegalDocumentPdfController::class, 'privacy'])
        ->name('legal.privacy.pdf');

    Route::get('/uvjeti-koristenja/pdf', [LegalDocumentPdfController::class, 'terms'])
        ->name('legal.terms.pdf');

    /*
    |--------------------------------------------------------------------------
    | Prihvaćanje / ponovno prihvaćanje uvjeta
    |--------------------------------------------------------------------------
    */

    Route::get('/prihvacanje-uvjeta', [LegalAcceptanceController::class, 'show'])
        ->name('legal.accept');

    Route::post('/prihvacanje-uvjeta', [LegalAcceptanceController::class, 'store'])
        ->name('legal.accept.store');

    /*
    |--------------------------------------------------------------------------
    | GDPR korisničke radnje
    |--------------------------------------------------------------------------
    */

    Route::post('/gdpr/povlacenje-privole', [LegalAcceptanceController::class, 'withdraw'])
        ->name('legal.withdraw');

    Route::get('/moji-podaci/export', [UserPrivacyController::class, 'export'])
        ->name('user.privacy.export');

    Route::post('/moj-racun/zahtjev-brisanje', [AccountDeletionController::class, 'requestDeletion'])
        ->name('account.deletion.request');

    Route::view('/ugovor-o-obradi-podataka', 'legal.dpa')
    ->name('legal.dpa');

    Route::view('/politika-sigurnosti', 'legal.security')
    ->name('legal.security');

    Route::view('/politika-zadrzavanja-podataka', 'legal.retention')
    ->name('legal.retention');

    Route::view('/cesto-postavljana-pitanja', 'legal.faq')
    ->name('legal.faq');

    Route::get('/politika-kolacica/pdf', [LegalDocumentPdfController::class, 'cookies'])
    ->name('legal.cookies.pdf');

    /*
    |--------------------------------------------------------------------------
    | Preview datoteka
    |--------------------------------------------------------------------------
    */

    Route::get('/preview-file', function (Request $request) {

        $file = ltrim((string) $request->query('file'), '/');

        abort_if($file === '', 404);

        abort_if(
            preg_match('#(^|/)\.\.(/|$)#', $file),
            403
        );

        abort_unless(
            Storage::disk('public')->exists($file),
            404
        );

        $fullPath = storage_path('app/public/' . $file);

        $fileName = basename($file);

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $mime = match ($extension) {
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => mime_content_type($fullPath) ?: 'application/octet-stream',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);

    })->name('file.preview');
});