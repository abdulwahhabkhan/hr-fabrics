<?php

use App\Http\Controllers\AutoCompleteController;
use Illuminate\Support\Facades\Route;

Route::prefix('autocomplete')
    ->middleware(['auth', 'verified'])
    ->as('autocomplete.')
    ->group(function () {
        Route::get('suppliers', [AutoCompleteController::class, 'suppliers'])->name('suppliers');
    });
