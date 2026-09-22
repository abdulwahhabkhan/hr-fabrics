<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('file')
    ->name('file.')
    ->group(function () {
        Route::post('upload', [FileController::class, 'upload'])->name('upload');
        Route::get('view', [FileController::class, 'view'])->name('view');
        Route::get('thumbnail', [FileController::class, 'thumbnail'])->name('thumbnail');
        Route::get('{file}/show', [FileController::class, 'show'])->name('show');
        Route::delete('{file}/delete', [FileController::class, 'delete'])->name('delete');
    });
