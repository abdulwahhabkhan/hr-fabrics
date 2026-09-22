<?php

use App\Http\Middleware\QueryKeyAuthMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
Route::name('api.')
    ->middleware(QueryKeyAuthMiddleware::class)
    ->group(function () {});
