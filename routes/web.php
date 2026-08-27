<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\TestAttemptController;
use App\Http\Controllers\ZnrGeneralReportController;
use App\Http\Controllers\LegalAcceptanceController;
use App\Http\Controllers\LegalDocumentPdfController;
use App\Http\Controllers\UserPrivacyController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\EmailTwoFactorController;
use App\Http\Controllers\PublicQr\MachineQrController;
use App\Http\Controllers\MachineQrAdminController;
use App\Http\Controllers\FireQrAdminController;
use App\Http\Controllers\PublicQr\FireQrController;


/*
|--------------------------------------------------------------------------
| JAVNE QR RUTE
|--------------------------------------------------------------------------
|
| Ove rute MORAJU ostati izvan auth middlewarea.
|
| Radnik, posjetitelj ili druga osoba mora moći skenirati QR kod
| i otvoriti podatke o konkretnoj radnoj opremi bez prijave
| u ZNR LIDER.
|
*/

Route::prefix('qr')
    ->middleware('throttle:120,1')
    ->group(function () {

        Route::get(
            '/machine/{token}',
            [
                MachineQrController::class,
                'show',
            ]
        )
            ->where(
                'token',
                '[A-Za-z0-9]{64}'
            )
            ->name(
                'public.machine.show'
            );

        Route::get(
            '/machine/{token}/qr.svg',
            [
                MachineQrController::class,
                'svg',
            ]
        )
            ->where(
                'token',
                '[A-Za-z0-9]{64}'
            )
            ->name(
                'public.machine.svg'
            );

        Route::get(
            '/machine/{token}/attachment/{index}',
            [
                MachineQrController::class,
                'attachment',
            ]
        )
            ->where(
                'token',
                '[A-Za-z0-9]{64}'
            )
            ->whereNumber('index')
            ->name(
                'public.machine.attachment'
            );
    });
    
    Route::get(
        '/fire/{token}',
        [
            FireQrController::class,
            'show',
        ]
    )
        ->where(
            'token',
            '[A-Za-z0-9]{64}'
        )
        ->name(
            'public.fire.show'
        );

    Route::get(
        '/fire/{token}/qr.svg',
        [
            FireQrController::class,
            'svg',
        ]
    )
        ->where(
            'token',
            '[A-Za-z0-9]{64}'
        )
        ->name(
            'public.fire.svg'
        );

    Route::get(
        '/fire/{token}/attachment/{index}',
        [
            FireQrController::class,
            'attachment',
        ]
    )
        ->where(
            'token',
            '[A-Za-z0-9]{64}'
        )
        ->whereNumber('index')
        ->name(
            'public.fire.attachment'
        );

/*
|--------------------------------------------------------------------------
| AUTENTIFICIRANE RUTE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Testovi
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/test-attempts/{attempt}',
        [TestAttemptController::class, 'show']
    )->name('test-attempts.show');

    Route::get(
        '/test-attempts/{attempt}/pdf',
        [TestAttemptController::class, 'downloadPdf']
    )->name('test-attempts.download');


    /*
    |--------------------------------------------------------------------------
    | ZNR General Report
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/admin/znr-general-report/pdf',
        [ZnrGeneralReportController::class, 'pdf']
    )->name('znr.general-report.pdf');


    /*
    |--------------------------------------------------------------------------
    | EMAIL 2FA za Super Admina
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/email-2fa/verify',
        [EmailTwoFactorController::class, 'verify']
    )->name('email-2fa.verify');

    Route::post(
        '/email-2fa/confirm',
        [EmailTwoFactorController::class, 'confirm']
    )->name('email-2fa.confirm');

    Route::post(
        '/email-2fa/resend',
        [EmailTwoFactorController::class, 'resend']
    )->name('email-2fa.resend');


    /*
    |--------------------------------------------------------------------------
    | GDPR / Pravni dokumenti
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/pravila-privatnosti',
        'legal.privacy'
    )->name('legal.privacy');

    Route::view(
        '/uvjeti-koristenja',
        'legal.terms'
    )->name('legal.terms');

    Route::view(
        '/politika-kolacica',
        'legal.cookies'
    )->name('legal.cookies');

    Route::get(
        '/pravila-privatnosti/pdf',
        [LegalDocumentPdfController::class, 'privacy']
    )->name('legal.privacy.pdf');

    Route::get(
        '/uvjeti-koristenja/pdf',
        [LegalDocumentPdfController::class, 'terms']
    )->name('legal.terms.pdf');


    /*
    |--------------------------------------------------------------------------
    | Prihvaćanje / ponovno prihvaćanje uvjeta
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/prihvacanje-uvjeta',
        [LegalAcceptanceController::class, 'show']
    )->name('legal.accept');

    Route::post(
        '/prihvacanje-uvjeta',
        [LegalAcceptanceController::class, 'store']
    )->name('legal.accept.store');


    /*
    |--------------------------------------------------------------------------
    | GDPR korisničke radnje
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/gdpr/povlacenje-privole',
        [LegalAcceptanceController::class, 'withdraw']
    )->name('legal.withdraw');

    Route::get(
        '/moji-podaci/export',
        [UserPrivacyController::class, 'export']
    )->name('user.privacy.export');

    Route::post(
        '/moj-racun/zahtjev-brisanje',
        [AccountDeletionController::class, 'requestDeletion']
    )->name('account.deletion.request');

    Route::view(
        '/ugovor-o-obradi-podataka',
        'legal.dpa'
    )->name('legal.dpa');

    Route::view(
        '/politika-sigurnosti',
        'legal.security'
    )->name('legal.security');

    Route::view(
        '/politika-zadrzavanja-podataka',
        'legal.retention'
    )->name('legal.retention');

    Route::view(
        '/cesto-postavljana-pitanja',
        'legal.faq'
    )->name('legal.faq');

    Route::get(
        '/politika-kolacica/pdf',
        [LegalDocumentPdfController::class, 'cookies']
    )->name('legal.cookies.pdf');


    /*
    |--------------------------------------------------------------------------
    | QR KODOVI - ADMINISTRACIJA RADNE OPREME
    |--------------------------------------------------------------------------
    |
    | Ove rute zahtijevaju prijavljenog korisnika.
    |
    | Sam controller dodatno provjerava:
    | - organizaciju
    | - tenant ownership
    | - module permissions
    |
    */

    Route::prefix('machine-qr')
        ->group(function () {

            Route::get(
                '/{machine}',
                [
                    MachineQrAdminController::class,
                    'show',
                ]
            )
                ->name(
                    'machine.qr.admin'
                );

            Route::post(
                '/{machine}/regenerate',
                [
                    MachineQrAdminController::class,
                    'regenerate',
                ]
            )
                ->name(
                    'machine.qr.regenerate'
                );

            Route::post(
                '/{machine}/deactivate',
                [
                    MachineQrAdminController::class,
                    'deactivate',
                ]
            )
                ->name(
                    'machine.qr.deactivate'
                );

            Route::post(
                '/{machine}/activate',
                [
                    MachineQrAdminController::class,
                    'activate',
                ]
            )
                ->name(
                    'machine.qr.activate'
                );
            Route::get(
                '/{machine}/download-pdf',
                [
                    MachineQrAdminController::class,
                    'downloadPdf',
                ]
            )
                ->name(
                    'machine.qr.download.pdf'
                );
        });

    Route::prefix('fire-qr')
        ->group(function () {

            Route::get(
                '/{fire}',
                [
                    FireQrAdminController::class,
                    'show',
                ]
            )
                ->name(
                    'fire.qr.admin'
                );

            Route::get(
                '/{fire}/download-pdf',
                [
                    FireQrAdminController::class,
                    'downloadPdf',
                ]
            )
                ->name(
                    'fire.qr.download.pdf'
                );

            Route::post(
                '/{fire}/regenerate',
                [
                    FireQrAdminController::class,
                    'regenerate',
                ]
            )
                ->name(
                    'fire.qr.regenerate'
                );

            Route::post(
                '/{fire}/deactivate',
                [
                    FireQrAdminController::class,
                    'deactivate',
                ]
            )
                ->name(
                    'fire.qr.deactivate'
                );

            Route::post(
                '/{fire}/activate',
                [
                    FireQrAdminController::class,
                    'activate',
                ]
            )
                ->name(
                    'fire.qr.activate'
                );

            Route::post(
                '/{fire}/regular-inspection',
                [
                    FireQrAdminController::class,
                    'regularInspection',
                ]
            )
                ->name(
                    'fire.qr.regular-inspection'
                );
        });
    /*
    |--------------------------------------------------------------------------
    | Siguran preview datoteka
    |--------------------------------------------------------------------------
    |
    | Datoteke se mogu otvoriti samo preko privremenog
    | potpisanog URL-a.
    |
    | Organizacijski korisnik smije koristiti samo URL
    | koji pripada njegovoj organizaciji.
    |
    | Superadmin može pregledavati sve priloge radi
    | administracije i korisničke podrške.
    |
    */

    Route::get(
        '/preview-file',
        function (Request $request) {
            $user = $request->user();

            abort_unless(
                $user,
                403
            );

            /*
             * Owner ID dolazi iz potpisanog URL-a.
             *
             * Kod superadmina:
             * owner = 0
             *
             * Kod organizacijskih korisnika:
             * owner = ownerId()
             */
            $ownerId = (int) $request->query(
                'owner',
                0
            );

            /*
             * Organizacijski korisnik smije koristiti
             * samo link svoje organizacije.
             *
             * Superadmin ovu provjeru preskače.
             */
            if (! $user->isSuperAdmin()) {
                $currentOwnerId = (int) $user->ownerId();

                abort_unless(
                    $currentOwnerId > 0
                    && $ownerId > 0
                    && $currentOwnerId === $ownerId,
                    403
                );
            }

            /*
             * Normalizacija putanje.
             */
            $file = str_replace(
                '\\',
                '/',
                (string) $request->query(
                    'file',
                    ''
                )
            );

            $file = ltrim(
                trim($file),
                '/'
            );

            abort_if(
                $file === '',
                404
            );

            /*
             * Zaštita od null-byte napada.
             */
            abort_if(
                str_contains(
                    $file,
                    "\0"
                ),
                403
            );

            /*
             * Zaštita od directory traversal napada:
             *
             * ../
             * /../
             * itd.
             */
            abort_if(
                preg_match(
                    '#(^|/)\.\.(/|$)#',
                    $file
                ) === 1,
                403
            );

            /*
             * Datoteka mora postojati
             * na Laravel public disku.
             */
            abort_unless(
                Storage::disk('public')
                    ->exists($file),
                404
            );

            /*
             * Dohvaćamo stvarnu putanju datoteke.
             */
            $fullPath = Storage::disk('public')
                ->path($file);

            $publicRoot = realpath(
                Storage::disk('public')
                    ->path('')
            );

            $resolvedPath = realpath(
                $fullPath
            );

            /*
             * Dodatna sigurnosna provjera.
             *
             * Čak i ako bi netko pokušao manipulirati
             * putanjom, stvarna datoteka mora ostati
             * unutar storage/app/public direktorija.
             */
            abort_unless(
                $publicRoot !== false
                && $resolvedPath !== false
                && (
                    $resolvedPath === $publicRoot
                    || str_starts_with(
                        $resolvedPath,
                        $publicRoot
                            . DIRECTORY_SEPARATOR
                    )
                ),
                403
            );

            $fileName = basename(
                $file
            );

            $extension = strtolower(
                pathinfo(
                    $fileName,
                    PATHINFO_EXTENSION
                )
            );

            /*
             * MIME tipovi koje aplikacija
             * standardno koristi.
             */
            $mime = match ($extension) {
                'pdf' =>
                    'application/pdf',

                'doc' =>
                    'application/msword',

                'docx' =>
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                'xls' =>
                    'application/vnd.ms-excel',

                'xlsx' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'jpg',
                'jpeg' =>
                    'image/jpeg',

                'png' =>
                    'image/png',

                'gif' =>
                    'image/gif',

                'webp' =>
                    'image/webp',

                default =>
                    mime_content_type(
                        $resolvedPath
                    )
                    ?: 'application/octet-stream',
            };

            /*
             * Sprječavamo ubacivanje znakova
             * koji bi mogli oštetiti HTTP zaglavlje.
             */
            $safeFileName = str_replace(
                [
                    "\r",
                    "\n",
                    '"',
                ],
                '',
                $fileName
            );

            return response()->file(
                $resolvedPath,
                [
                    'Content-Type' =>
                        $mime,

                    'Content-Disposition' =>
                        'inline; filename="'
                        . $safeFileName
                        . '"',

                    'X-Content-Type-Options' =>
                        'nosniff',

                    /*
                     * Dokumenti organizacije ne spremaju
                     * se u javni browser cache.
                     */
                    'Cache-Control' =>
                        'private, no-store, max-age=0',
                ]
            );
        }
    )
        ->middleware('signed')
        ->name('file.preview');
});