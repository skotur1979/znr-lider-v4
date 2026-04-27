<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestAttemptController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Http\Controllers\ZnrGeneralReportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/test-attempts/{attempt}', [TestAttemptController::class, 'show'])
        ->name('test-attempts.show');

    Route::get('/test-attempts/{attempt}/pdf', [TestAttemptController::class, 'downloadPdf'])
        ->name('test-attempts.download');
        
    Route::get('/admin/znr-general-report/pdf', [ZnrGeneralReportController::class, 'pdf'])
    ->name('znr.general-report.pdf');

    Route::get('/preview-file', function (Request $request) {
    $file = ltrim((string) $request->query('file'), '/');

    abort_if($file === '', 404);
    abort_if(preg_match('#(^|/)\.\.(/|$)#', $file), 403);
    abort_unless(Storage::disk('public')->exists($file), 404);

    $fullPath = storage_path('app/public/' . $file);
    $fileName = basename($file);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $mime = match ($extension) {
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
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