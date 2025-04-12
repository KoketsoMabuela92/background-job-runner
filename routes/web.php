<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackgroundJobController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('jobs.index');
});

Route::prefix('jobs')->group(function () {
    Route::get('/', [BackgroundJobController::class, 'index'])->name('jobs.index');
    Route::get('/create', [BackgroundJobController::class, 'create'])->name('jobs.create');
    Route::post('/', [BackgroundJobController::class, 'store'])->name('jobs.store');
    Route::get('/logs', [BackgroundJobController::class, 'logs'])->name('jobs.logs');
    Route::get('/{job}', [BackgroundJobController::class, 'show'])->name('jobs.show');
    Route::post('/{job}/cancel', [BackgroundJobController::class, 'cancel'])->name('jobs.cancel');
    Route::post('/{job}/retry', [BackgroundJobController::class, 'retry'])->name('jobs.retry');
});
