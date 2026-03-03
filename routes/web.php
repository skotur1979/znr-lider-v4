<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestAttemptController;

Route::middleware(['auth'])->group(function () {
    Route::get('/test-attempts/{attempt}', [TestAttemptController::class, 'show'])
        ->name('test-attempts.show');

    Route::get('/test-attempts/{attempt}/pdf', [TestAttemptController::class, 'downloadPdf'])
        ->name('test-attempts.download');
});