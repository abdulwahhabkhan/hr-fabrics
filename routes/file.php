<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FileThumbnailController;
use App\Http\Controllers\FileViewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('file')
    ->name('file.')
    ->group(function () {
        Route::post('upload', [FileController::class, 'store'])->name('upload');
        Route::get('view', FileViewController::class)->name('view');
        Route::get('thumbnail', FileThumbnailController::class)->name('thumbnail');
        Route::get('{file}/show', [FileController::class, 'show'])->name('show');
        Route::delete('{file}/delete', [FileController::class, 'destroy'])->name('delete');
    });
